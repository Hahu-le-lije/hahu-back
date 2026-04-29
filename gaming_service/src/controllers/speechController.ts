import { type Request, type Response } from "express";
import {HfInference} from '@huggingface/inference';
import {distance} from 'fastest-levenshtein';
import { error } from "node:console";

const hf=new HfInference(process.env.HUG_FACE);

export const speechController=async(req:Request,res:Response)=>{
    try{
        const {targetWord}=req.body;
        const audioFile=req.file;
        if(!audioFile){
            return res.status(400).json({error:"No audio is uploaded"});
        }
        if(!targetWord){
            return res.status(400).json({error:"Target word is required"});
        }
        const audioBlob = new Blob([new Uint8Array(audioFile.buffer)], { type: audioFile.mimetype });
        const result=await hf.automaticSpeechRecognition({
            model:"openai/whisper-large-v3",
            inputs:audioBlob,
            parameters:{
                language:"am"
            }
        })
        const heardText=result.text.trim();
        console.log(heardText);
        const d=distance(targetWord,heardText);
        const longest=Math.max(targetWord.length,heardText.length);

        const score=longest===0 ? 0: Math.round(((longest-d)/longest)*100);

        res.status(200).json({
            success:true,
            score:Math.max(0,score),
            heard:heardText,
            expected:targetWord
        })
    }catch(err){
        console.log("Hf inference error: ",err);
        res.status(500).json({
            success:false,
            error: "Ai service is currently unavailable. please try again later"
        })
    }
}