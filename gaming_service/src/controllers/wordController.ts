import 'dotenv/config';
import type {Request,Response} from 'express'
import {GoogleGenerativeAI} from '@google/generative-ai'
import type {WordRequest,WordResponse} from '../types/word.js'
import {createClient} from 'redis';

const generativeAI=new GoogleGenerativeAI(process.env.GEMINI_API_KEY as string)
const geminiModelName = process.env.GEMINI_MODEL ?? "gemini-3.0-flash"
const redisClient=createClient({
    url:process.env.REDIS_URL
})
redisClient.on('error',err=>console.error('Redis Client Error',err));

await redisClient.connect();
export  const wordDetails=async(req:Request<{},{},WordRequest>,res:Response)=>{
    try{
        const {word,language}=req.body;
        if(!word){
            return res.status(400).json({error:"word is required"})
        }
    const cacheKey=`word:${language.toLowerCase()}:${word.trim().toLowerCase()}`;

    const cache=await redisClient.get(cacheKey);
    if(cache){
        console.log("Cache hit")
        return res.status(200).json(JSON.parse(cache));
    }
    console.log("Cache miss, calling gemini for ",word )

    const model=generativeAI.getGenerativeModel({model:geminiModelName
        ,generationConfig:{responseMimeType:"application/json"}
    });
    const prompt = `
        You are an expert Amharic language teacher for children.
        Target Word: "${word}"
        Instruction Language: "${language}"

        Task:
        1. Provide the definition of "${word}" in ${language}.
        2. Provide 5 simple, child-friendly Amharic sentences using "${word}".
        3. Provide a phonetic pronunciation guide using Latin characters (e.g., "Selam" for "ሰላም").
        4. If ${language} is English, translate the 5 Amharic sentences into English.

        Return strictly in this JSON format:
        {
          "word": "${word}",
          "definition": "definition here",
          "phonetic": "phonetic here",
          "learning_content": [
            { "amharic": "sentence 1", "translation": "translation here" },
            { "amharic": "sentence 2", "translation": "translation here" },
            { "amharic": "sentence 3", "translation": "translation here" },
            { "amharic": "sentence 4", "translation": "translation here" },
            { "amharic": "sentence 5", "translation": "translation here" }
          ]
        }`;
    const result=await model.generateContent(prompt);
    const responseText=result.response.text();
    const parse=JSON.parse(responseText);
    await redisClient.set(cacheKey,JSON.stringify(parse),{
        EX:604800
    });
    console.log("Gemini raw output:", responseText)
    return res.status(200).json(parse as WordResponse);
    }catch(error){
        const err = error as {status?: number; message?: string};
        console.log("Error: ", error);
        res.status(500).json({
            error:"Teacher is busy, try again",
            details: err.status ? `Gemini API error ${err.status}: ${err.message ?? "unknown error"}` : undefined
        })
    }
}