import React from "react";
import { View, StyleSheet } from "react-native";
import Homepage from "./pages/homepage";

const App = () => {
  return (
    <View style={styles.container}>
      <Homepage />
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
