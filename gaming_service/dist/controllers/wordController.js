import { GoogleGenerativeAI } from '@google/generative-ai';
const generativeAI = new GoogleGenerativeAI(process.env.GEMINI_API_KEY);
console.log("GEMINI_API_KEY", process.env.GEMINI_API_KEY);
export const wordDetails = async (req, res) => {
    try {
        const { word, language } = req.body;
        if (!word) {
            return res.status(400).json({ error: "word is required" });
            //might need to also add iss with child service to get sub type
        }
        const model = generativeAI.getGenerativeModel({ model: "gemini-1.5-flash",
            generationConfig: { responseMimeType: "application/json" }
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
        const result = await model.generateContent(prompt);
        const responseText = result.response.text();
        console.log("Gemini raw output:", responseText);
        const cleaned = responseText
            .replace(/```json/g, "")
            .replace(/```/g, "")
            .trim();
        let parsed;
        try {
            parsed = JSON.parse(cleaned);
        }
        catch (error) {
            console.log("JSON parse Failed: ", cleaned);
            return res.status(500).json({
                error: "JSON parse Failed",
                raw: cleaned
            });
        }
        res.status(200).json(parsed);
    }
    catch (error) {
        console.error("FULL ERROR:", JSON.stringify(error, null, 2));
        res.status(500).json({ error: "Teacher is busy, try again" });
    }
};
//# sourceMappingURL=wordController.js.map