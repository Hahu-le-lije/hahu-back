import 'dotenv/config';
import express, {} from 'express';
import cors from 'cors';
import wordRoutes from './routes/wordRoutes.js';
const app = express();
app.use(cors());
app.use(express.json());
app.use(express.urlencoded({ extended: true }));
app.use('/game/word', wordRoutes);
const port = process.env.PORT || 5000;
app.listen(port, () => console.log(`Server is running on port http://localhost:${port} `));
//# sourceMappingURL=server.js.map