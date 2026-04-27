import type {Request,Response} from 'express'
import {GoogleGenerativeAI} from '@google/generative-ai'
import type {WordRequest,WordResponse} from '../types/word.ts'

const generativeAI=new GoogleGenerativeAI(process.env.GEMINI_API_KEY as string)

export const wordDetails=async(req:Request<{},{},WordRequest>,res:Response)=>{
    try{
        const {word,language}=req.body;
        if(!word){
            return res.status(400).json({error:"word is required"})
            //might need to also add iss with child service to get sub type
        }
    const model=generativeAI.getGenerativeModel({model:"gemini-1.5-flash"
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
    res.status(200).json(JSON.parse(responseText) as WordResponse);
    }catch(error){
        res.status(500).json({error:"Teacher is busy, try again"})
    }
}