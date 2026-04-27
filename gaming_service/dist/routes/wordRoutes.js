import { Router } from "express";
import { wordDetails } from "../controllers/wordContorller.js";
const router = Router();
router.post("/", wordDetails);
export default router;
//# sourceMappingURL=wordRoutes.js.map