export interface WordResponse {
  word: string;
  definition: string;
  phonetic: string;
  learning_content: {
    amharic: string;
    translation?: string;
  }[];
}

export interface WordRequest {
    word: string;
    language: string
}