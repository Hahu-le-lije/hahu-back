import * as sdk from 'microsoft-cognitiveservices-speech-sdk';
import { resolve } from 'node:dns';

export const evaluatePronunciation=async(audioBuffer:Buffer,referenceText:string)=>{
    const speechConfig=sdk.SpeechConfig.fromSubscription(
        process.env.AZURE_SPEECH_KEY as string,
        process.env.AZURE_SPEECH_REGION as string
    )
    speechConfig.speechRecognitionLanguage='am-ET';

    const audioConfig=sdk.AudioConfig.fromWavFileInput(audioBuffer);
    const recognizer=new sdk.SpeechRecognizer(speechConfig,audioConfig);

    const pronunciationConfig=new sdk.PronunciationAssessmentConfig(
        referenceText,
        sdk.PronunciationAssessmentGradingSystem.HundredMark,
        sdk.PronunciationAssessmentGranularity.Phoneme,
    );

    pronunciationConfig.applyTo(recognizer);

    return new Promise((resolve,reject)=>{
        recognizer.recognizeOnceAsync(result=>{
            const assessmentResult=sdk.PronunciationAssessmentResult.fromResult(result);
            resolve({
                accuracyScore:assessmentResult.accuracyScore,
                fluencyScore:assessmentResult.fluencyScore,
                completenessScore:assessmentResult.completenessScore
            });
            recognizer.close();
        })
    })
}