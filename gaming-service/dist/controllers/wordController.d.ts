import 'dotenv/config';
import type { Request, Response } from 'express';
import type { WordRequest } from '../types/word.js';
export declare const wordDetails: (req: Request<{}, {}, WordRequest>, res: Response) => Promise<Response<any, Record<string, any>> | undefined>;
