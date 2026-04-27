import { Router } from "express";
import { wordDetails } from "../controllers/wordController.js";
const router = Router();
router.post("/", wordDetails);
export default router;
//# sourceMappingURL=wordRoutes.js.map