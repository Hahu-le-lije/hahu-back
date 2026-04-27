import 'dotenv/config';
import express,{ type Application} from 'express';
import cors from 'cors';
import wordRoutes from './routes/wordRoutes.js';

const app: Application = express();

app.use(cors());
app.use(express.json());
app.use(express.urlencoded({ extended: true }));
app.use((req, _res, next) => {
  console.log("[REQ]", req.method, req.originalUrl, req.body);
  next();
});

app.use('/game/word',wordRoutes);

const port=process.env.PORT || 5000;

app.listen(port,()=>console.log(`Server is running on port http://localhost:${port} `));