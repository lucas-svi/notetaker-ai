import axios from "axios";

const api = axios.create({
  baseURL: "http://10.0.2.2/notetaker-ai/backend/index.php",
  headers: {
    "Content-Type": "application/x-www-form-urlencoded",
  },
});

export default api;
