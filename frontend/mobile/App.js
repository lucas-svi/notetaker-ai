import React from "react";
import { View, StyleSheet } from "react-native";
import NotesView from "./pages/NotesView";

const App = () => {
  return (
    <View style={styles.container}>
      <NotesView />
    </View>
  );
};

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: "#fff",
  },
});

export default App;
