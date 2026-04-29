import { Router } from "express";
import multer from 'multer';
import { wordDetails } from "../controllers/wordController.js";
import { speechController } from "../controllers/speechController.js";
const router: Router = Router();
const upload=multer({storage:multer.memoryStorage()})

router.post("/word", wordDetails);
router.post('/speech',upload.single('audio'),speechController)

export default router;