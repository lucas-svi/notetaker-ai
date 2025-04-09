import React, { useState, useEffect } from "react";
import {
  View,
  Text,
  StyleSheet,
  TouchableOpacity,
  ScrollView,
  Alert,
  Platform,
  StatusBar,
} from "react-native";
import api from "../api";
import NoteForm from "./NoteForm";

const NotesView = () => {
  const [notes, setNotes] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [currentUser, setCurrentUser] = useState("a");
  const [showForm, setShowForm] = useState(false);
  const [selectedNote, setSelectedNote] = useState(null);

  useEffect(() => {
    fetchNotes();
  }, []);

  const fetchNotes = async () => {
    try {
      const response = await api.get("/note/list");
      setNotes(response.data);
      setLoading(false);
    } catch (err) {
      setError(err.message);
      setLoading(false);
    }
  };

  const handleDelete = async (noteId) => {
    Alert.alert(
      "Delete Note",
      "Are you sure you want to delete this note?",
      [
        {
          text: "Cancel",
          style: "cancel",
        },
        {
          text: "Delete",
          style: "destructive",
          onPress: async () => {
            try {
              await api.delete(`/note/delete?id=${noteId}`);
              fetchNotes();
            } catch (error) {
              Alert.alert("Error", "Failed to delete note");
            }
          },
        },
      ],
      { cancelable: true }
    );
  };

  const handleEditNote = (note) => {
    setSelectedNote(note);
    setShowForm(true);
  };

  const handleCreateNote = () => {
    setSelectedNote(null);
    setShowForm(true);
  };

  const handleFormSubmit = () => {
    setShowForm(false);
    setSelectedNote(null);
    fetchNotes(); // Refresh the notes list
  };

  if (showForm) {
    return (
      <View style={styles.container}>
        <NoteForm
          noteId={selectedNote?.id}
          initialContent={selectedNote?.note || ""}
          onSubmitSuccess={handleFormSubmit}
          username={currentUser}
        />
      </View>
    );
  }

  if (loading) {
    return (
      <View style={styles.container}>
        <Text>Loading notes...</Text>
      </View>
    );
  }

  if (error) {
    return (
      <View style={styles.container}>
        <Text style={styles.error}>Error: {error}</Text>
      </View>
    );
  }

  // I used generative AI to help with the styling and overall layout of this page
  return (
    <View style={styles.container}>
      <View style={styles.statusBar} />
      <View style={styles.header}>
        <Text style={styles.title}>All Notes</Text>
        <TouchableOpacity
          style={styles.createButton}
          onPress={handleCreateNote}
        >
          <Text style={styles.createButtonText}>+ New Note</Text>
        </TouchableOpacity>
      </View>

      <ScrollView style={styles.notesList}>
        {notes.map((note) => (
          <View key={note.id} style={styles.noteCard}>
            <View style={styles.noteHeader}>
              <Text style={styles.username}>@{note.username}</Text>
            </View>

            <Text style={styles.noteContent}>{note.note}</Text>

            {note.username === currentUser && (
              <View style={styles.actions}>
                <TouchableOpacity
                  style={[styles.button, styles.editButton]}
                  onPress={() => handleEditNote(note)}
                >
                  <Text style={styles.buttonText}>Edit</Text>
                </TouchableOpacity>

                <TouchableOpacity
                  style={[styles.button, styles.deleteButton]}
                  onPress={() => handleDelete(note.id)}
                >
                  <Text style={styles.buttonText}>Delete</Text>
                </TouchableOpacity>
              </View>
            )}
          </View>
        ))}
      </ScrollView>
    </View>
  );
};

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: "#fff",
  },
  statusBar: {
    height: Platform.OS === "android" ? StatusBar.currentHeight : 0,
    backgroundColor: "#fff",
  },
  header: {
    flexDirection: "row",
    justifyContent: "space-between",
    alignItems: "center",
    padding: 16,
    borderBottomWidth: 1,
    borderBottomColor: "#eee",
  },
  title: {
    fontSize: 24,
    fontWeight: "bold",
  },
  createButton: {
    backgroundColor: "#007AFF",
    padding: 10,
    borderRadius: 5,
  },
  createButtonText: {
    color: "white",
    fontWeight: "bold",
  },
  notesList: {
    flex: 1,
    padding: 16,
  },
  noteCard: {
    backgroundColor: "#f8f8f8",
    padding: 16,
    borderRadius: 8,
    marginBottom: 16,
    borderWidth: 1,
    borderColor: "#eee",
  },
  noteHeader: {
    flexDirection: "row",
    justifyContent: "space-between",
    marginBottom: 8,
  },
  username: {
    fontWeight: "bold",
    color: "#007AFF",
  },
  date: {
    color: "#666",
    fontSize: 12,
  },
  noteContent: {
    fontSize: 16,
    lineHeight: 24,
    marginBottom: 12,
  },
  actions: {
    flexDirection: "row",
    justifyContent: "flex-end",
    gap: 8,
  },
  button: {
    padding: 8,
    borderRadius: 4,
    minWidth: 80,
    alignItems: "center",
  },
  editButton: {
    backgroundColor: "#007AFF",
  },
  deleteButton: {
    backgroundColor: "#FF3B30",
  },
  buttonText: {
    color: "white",
    fontWeight: "bold",
  },
  error: {
    color: "red",
    textAlign: "center",
  },
});

export default NotesView;
