import { type Request, type Response } from "express";
import { HfInference } from "@huggingface/inference";
import { distance } from "fastest-levenshtein";

const hf = new HfInference(process.env.HUG_FACE);

export const speechController = async (req: Request, res: Response) => {
    try {
        const { targetWord } = req.body;
        const audioFile = req.file;

        if (!audioFile) {
            return res.status(400).json({
                error: "No audio is uploaded",
            });
        }

        if (!targetWord) {
            return res.status(400).json({
                error: "Target word is required",
            });
        }

        console.log("Audio mimetype:", audioFile.mimetype);
        console.log("Audio filename:", audioFile.originalname);

        let mimeType = audioFile.mimetype;

        if (
            mimeType === "audio/m4a" ||
            mimeType === "audio/x-m4a"
        ) {
            console.log("Converting MIME type to audio/mp4");
            mimeType = "audio/mp4";
        }

        const audioBlob = new Blob(
            [new Uint8Array(audioFile.buffer)],
            { type: mimeType }
        );

        const result = await hf.automaticSpeechRecognition({
            model: "openai/whisper-large-v3",
            inputs: audioFile.buffer,
            parameters: {
                language: "am",
            },
        });

        const heardText = result.text.trim();

        console.log("Recognized text:", heardText);

        const d = distance(targetWord, heardText);
        const longest = Math.max(
            targetWord.length,
            heardText.length
        );

        const score =
            longest === 0
                ? 0
                : Math.round(((longest - d) / longest) * 100);

        return res.status(200).json({
            success: true,
            score: Math.max(0, score),
            heard: heardText,
            expected: targetWord,
        });
    } catch (err) {
        console.error("Hf inference error:", err);

        return res.status(500).json({
            success: false,
            error:
                "Ai service is currently unavailable. please try again later",
        });
    }
};
