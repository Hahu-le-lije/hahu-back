import { Router } from "express";

import { wordDetails } from "../controllers/wordController.js";

const router: Router = Router();

router.post("/", wordDetails);

export default router;