import React, { useState } from "react";
import { View, TextInput, Button, Text, StyleSheet } from "react-native";
import api from "../api"; // Import the axios instance from api.js

const NoteForm = ({
  noteId = null,
  initialContent = "",
  onSubmitSuccess = () => {},
  username = "a", // Default username or pass as a prop
}) => {
  // State for form fields
  const [content, setContent] = useState(initialContent);
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState("");

  // Function to handle note creation or update
  const handleSubmit = async () => {
    if (!content.trim()) {
      setError("Note content cannot be empty");
      return;
    }

    setIsLoading(true);
    setError("");

    const noteData = new URLSearchParams();
    noteData.append("username", username);
    noteData.append("note", content);

    try {
      if (noteId) {
        noteData.append("id", noteId);
        // Update note
        await api.put(`/note/update`, noteData);
      } else {
        // Create new note
        await api.post("/note/create", noteData);
      }

      onSubmitSuccess(); // This should trigger navigation back
    } catch (error) {
      setError(error.response?.data || error.message);
    } finally {
      setIsLoading(false);
    }
  };

  return (
    <View style={styles.container}>
      <Text style={styles.header}>
        {noteId ? "Update Note" : "Create New Note"}
      </Text>

      <TextInput
        style={styles.input}
        placeholder="Enter your note here..."
        value={content}
        onChangeText={setContent}
        multiline
      />

      {error && <Text style={styles.error}>{error}</Text>}

      <Button
        title={isLoading ? "Saving..." : noteId ? "Update Note" : "Create Note"}
        onPress={handleSubmit}
        disabled={isLoading}
      />
    </View>
  );
};

const styles = StyleSheet.create({
  container: {
    flex: 1,
    justifyContent: "center",
    alignItems: "center",
    padding: 16,
  },
  header: {
    fontSize: 24,
    marginBottom: 20,
  },
  input: {
    width: "100%",
    padding: 10,
    marginBottom: 15,
    borderWidth: 1,
    borderColor: "#ccc",
    borderRadius: 5,
  },
  error: {
    color: "red",
    marginBottom: 10,
  },
});

export default NoteForm;
