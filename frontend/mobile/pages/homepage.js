import React, { Component } from "react";
import { StyleSheet, Button, Text, View } from "react-native";

class Homepage extends Component {
  state = {
    isLoggedIn: false,
  };

  handleLogin = () => {
    // Replace with actual authentication logic
    this.setState({ isLoggedIn: true });
  };

  handleLogout = () => {
    // Replace with actual logout logic
    this.setState({ isLoggedIn: false });
  };

  render() {
    const { isLoggedIn } = this.state;
    return (
      <View style={styles.container}>
        <Text style={styles.title}>Welcome to Notetaker AI!</Text>

        {!isLoggedIn ? (
          // Show Log In and Sign Up when not logged in
          <>
            <View style={styles.buttonContainer}>
              <Button title="Log In" onPress={this.handleLogin} />
            </View>
            <View style={styles.buttonContainer}>
              <Button title="Sign Up" onPress={() => {
                // Insert sign-up logic here
              }} />
            </View>
          </>
        ) : (
          // When logged in, show Access Notes and Log Out buttons
          <>
            <View style={styles.buttonContainer}>
              <Button title="Access Notes" onPress={() => {
                // Insert navigation to notes screen logic here
              }} />
            </View>
            <View style={styles.buttonContainer}>
              <Button title="Log Out" onPress={this.handleLogout} />
            </View>
          </>
        )}
      </View>
    );
  }
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    alignItems: "center",
    justifyContent: "center",
    padding: 20,
    backgroundColor: "#fff",
  },
  title: {
    fontSize: 24,
    marginBottom: 40,
    fontWeight: "bold",
  },
  buttonContainer: {
    marginVertical: 10,
    width: "80%",
  },
});

export default Homepage;
