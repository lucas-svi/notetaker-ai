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

class SignupForm extends Component {
  state = {
    name: "",
    email: "",
    password: "",
    confirm_password: "",
    isLoading: false,
  };

  handleNameChange = (name) => {
    this.setState({ name });
  };

  handleEmailChange = (email) => {
    this.setState({ email });
  };

  handlePasswordChange = (password) => {
    this.setState({ password });
  };

  handleConfirmPasswordChange = (confirm_password) => {
    this.setState({ confirm_password });
  };

  handleSignup = async () => {
    const { name, email, password, confirm_password } = this.state;
    const { onSignupSuccess } = this.props;
    
    // Basic validation
    if (!name || !email || !password || !confirm_password) {
      Alert.alert("Error", "Please enter name, email, password, and confirm password");
      return;
    }
    
    this.setState({ isLoading: true });
    
    try {
    console.log("Signing up...");
      // Replace with your actual signup endpoint as needed
      const response = await fetch("http://10.0.2.2/notetaker-ai/backend/index.php/user/create", {
        method: "POST",
        headers: {
            "Content-Type": "application/x-www-form-urlencoded",
        },
        body: `username=${encodeURIComponent(name)}&email=${encodeURIComponent(email)}&password=${encodeURIComponent(password)}&confirm_password=${encodeURIComponent(confirm_password)}`
      });
      const responseText = await response.text();
      console.log("Response Text:", responseText);
      
      if (!response.ok) {
        throw new Error(responseText || "Signup failed");
      }
      
      // On successful signup, assume the user is logged in
      onSignupSuccess(responseText);
    } catch (error) {
      Alert.alert(
        "Signup Failed",
        error.message || "Please check your details and try again"
      );
    } finally {
      this.setState({ isLoading: false });
    }
  };

  render() {
    const { name, email, password, confirm_password, isLoading } = this.state;
    const { onCancel } = this.props;
    
    return (
      <View style={styles.formContainer}>
        <Text style={styles.title}>Sign Up</Text>
        
        <TextInput
          style={styles.input}
          placeholder="Name"
          value={name}
          onChangeText={this.handleNameChange}
          autoCapitalize="words"
        />
        
        <TextInput
          style={styles.input}
          placeholder="Email"
          value={email}
          onChangeText={this.handleEmailChange}
          keyboardType="email-address"
          autoCapitalize="none"
        />
        
        <TextInput
          style={styles.input}
          placeholder="Password"
          value={password}
          onChangeText={this.handlePasswordChange}
          secureTextEntry
        />

        <TextInput
          style={styles.input}
          placeholder="Confirm Password"
          value={confirm_password}
          onChangeText={this.handleConfirmPasswordChange}
          secureTextEntry
        />

        <View style={styles.buttonContainer}>
          <Button
            title={isLoading ? "Signing up..." : "Sign Up"}
            onPress={this.handleSignup}
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

export default SignupForm;
