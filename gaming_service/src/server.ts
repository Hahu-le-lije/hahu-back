import 'dotenv/config';
import express,{ Application} from 'express';
import cors from 'cors';
import wordRoutes from './routes/wordRoutes';

const app: Application = express();

app.use(cors());
app.use(express.json());

app.use('/game/word',wordRoutes);

const port=process.env.PORT || 5000;

app.listen(port,()=>console.log(`Server is running on port ${port}`));