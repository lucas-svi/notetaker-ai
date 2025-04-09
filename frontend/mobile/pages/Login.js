import React, { Component } from "react";

import {
  StyleSheet,
  TextInput,
  Button,
  Text,
  View,
  TouchableOpacity,
  Alert,
} from "react-native";

class LoginForm extends Component {
  state = {
    password: "",
    isLoading: false,
  };

  handleUsernameChange = (username) => {
    this.setState({ username });
  };

  handlePasswordChange = (password) => {
    this.setState({ password });
  };

  handleLogin = async () => {
    const { username, password } = this.state;
    // Use the onLoginSuccess callback passed as a prop to update the parent state
    const { onLoginSuccess } = this.props;
    
    // Basic validation
    if (!username || !password) {
      Alert.alert("Error", "Please enter both username and password");
      return;
    }
    
    this.setState({ isLoading: true });
    
    try {
      const response = await fetch("http://10.0.2.2/notetaker-ai/backend/index.php/user/login", {
        method: "POST",
        headers: {
            "Content-Type": "application/x-www-form-urlencoded",
        },
        body: `username=${encodeURIComponent(username)}&password=${encodeURIComponent(password)}`
      });
      const responseText = await response.text();
      if (!response.ok) {
        throw new Error(responseText || "Login failed");
      }
      
      // On successful login, call the callback function
      onLoginSuccess(responseText);
    } catch (error) {
      Alert.alert(
        "Login Failed",
        error.message || "Please check your credentials and try again"
      );
    } finally {
      this.setState({ isLoading: false });
    }
  };

  render() {
    const { username, password, isLoading } = this.state;
    const { onCancel } = this.props;
    
    return (
      <View style={styles.formContainer}>
        <Text style={styles.title}>Log In</Text>
        
        <TextInput
          style={styles.input}
          placeholder="Username"
          value={username}
          onChangeText={this.handleUsernameChange}
          keyboardType="username"
          autoCapitalize="none"
        />
        
        <TextInput
          style={styles.input}
          placeholder="Password"
          value={password}
          onChangeText={this.handlePasswordChange}
          secureTextEntry
        />
        
        <View style={styles.buttonContainer}>
          <Button
            title={isLoading ? "Logging in..." : "Log In"}
            onPress={this.handleLogin}
            disabled={isLoading}
          />
        </View>
        
        <TouchableOpacity onPress={onCancel} style={styles.linkContainer}>
          <Text style={styles.link}>Cancel</Text>
        </TouchableOpacity>
      </View>
    );
  }
}

const styles = StyleSheet.create({
  formContainer: {
    marginTop: 20,
    padding: 20,
    borderColor: "#ccc",
    borderWidth: 1,
    borderRadius: 5,
    width: "80%",
    alignSelf: "center",
  },
  title: {
    fontSize: 20,
    marginBottom: 10,
    fontWeight: "bold",
    textAlign: "center",
  },
  input: {
    width: "100%",
    height: 40,
    borderWidth: 1,
    borderColor: "#ccc",
    borderRadius: 5,
    marginBottom: 10,
    paddingHorizontal: 8,
  },
  buttonContainer: {
    marginVertical: 10,
  },
  linkContainer: {
    alignItems: "center",
    marginTop: 10,
  },
  link: {
    color: "blue",
    textDecorationLine: "underline",
  },
});

export default LoginForm;
