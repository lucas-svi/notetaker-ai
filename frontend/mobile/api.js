import axios from "axios";

const api = axios.create({
  baseURL: "http://172.21.243.24/notetaker-ai/backend/index.php",
});

export default api;
