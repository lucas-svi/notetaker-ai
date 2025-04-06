import React, { useState, useEffect } from "react";
import { Text, View } from "react-native";
import api from "./api"; // Import the api instance

const HelloWorldApp = () => {
  // State to store the fetched notes
  const [notes, setNotes] = useState(null);
  const [error, setError] = useState(null); // Optional: To store any error that occurs

  // Fetch notes on component mount
  useEffect(() => {
    const fetchNotes = async () => {
      try {
        const response = await api.get("/note/list");
        setNotes(response.data); // Set the response data to the state
      } catch (error) {
        setError(error.message); // Store any error that occurs
      }
    };

    fetchNotes();
  }, []); // Empty dependency array means this runs only once when the component mounts

  return (
    <View style={{ flex: 1, justifyContent: "center", alignItems: "center" }}>
      {/* Display error if any */}
      {error ? (
        <Text>Error: {error}</Text>
      ) : // Show the notes data if available
      notes ? (
        <Text>{JSON.stringify(notes)}</Text> // Display the notes
      ) : (
        <Text>Loading...</Text> // Show loading state
      )}
    </View>
  );
};

export default HelloWorldApp;
