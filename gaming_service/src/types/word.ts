export interface WordResponse {
    meaning: string;
    sentences: string[];
    phonetic: string;
}

export interface WordRequest {
    word: string;
    language: string
}