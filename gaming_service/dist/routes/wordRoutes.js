import { Router } from "express";
import multer from 'multer';
import { wordDetails } from "../controllers/wordController.js";
import { speechController } from "../controllers/speechController.js";
const router = Router();
const upload = multer({ storage: multer.memoryStorage() });
router.post("/", wordDetails);
router.post('/speech', upload.single('audio'), speechController);
export default router;
//# sourceMappingURL=wordRoutes.js.map