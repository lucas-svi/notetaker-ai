import React, { useState } from "react";
import { View, Text, Button, StyleSheet } from "react-native";
import LoginForm from "./Login";
import SignupForm from "./Signup";
import NotesView from "./NotesView";

const Homepage = () => {
  // Local state for authentication and form toggling
  const [isLoggedIn, setIsLoggedIn] = useState(false);
  const [showLogin, setShowLogin] = useState(false);
  const [showSignup, setShowSignup] = useState(false);
  const [userData, setUserData] = useState(null);

  // Callback when login is successful
  const handleLoginSuccess = (data) => {
    setUserData(data);
    setIsLoggedIn(true);
    setShowLogin(false);
    setShowSignup(false);
  };

  // Callback when signup is successful
  const handleSignupSuccess = (data) => {
    setUserData(data);
    setIsLoggedIn(true);
    setShowLogin(false);
    setShowSignup(false);
  };

  const handleLogout = () => {
    setIsLoggedIn(false);
    setUserData(null);
  };

  // If logged in, display NotesView
  if (isLoggedIn) {
    console.log(userData);
    const userDataObj = typeof userData === 'string' ? JSON.parse(userData) : userData;
    return (
      <View style={styles.container}>
        <View style={styles.header}>
          <Text style={styles.welcomeText}>
            Welcome, {userDataObj.user.username}!
          </Text>
          <Button title="Log Out" onPress={handleLogout} />
        </View>
        <NotesView userData={userDataObj.user} />
      </View>
    );
  }

  // Otherwise display the login/sign up buttons and forms (if toggled)
  return (
    <View style={styles.container}>
      <Text style={styles.title}>Welcome to Notetaker AI!</Text>
      <View style={styles.buttonContainer}>
        <Button
          title="Log In"
          onPress={() => {
            setShowLogin(!showLogin);
            if (showSignup) setShowSignup(false);
          }}
        />
      </View>
      {showLogin && (
        <LoginForm
          onLoginSuccess={handleLoginSuccess}
          onCancel={() => setShowLogin(false)}
        />
      )}
      <View style={styles.buttonContainer}>
        <Button
          title="Sign Up"
          onPress={() => {
            setShowSignup(!showSignup);
            if (showLogin) setShowLogin(false);
          }}
        />
      </View>
      {showSignup && (
        <SignupForm
          onSignupSuccess={handleSignupSuccess}
          onCancel={() => setShowSignup(false)}
        />
      )}
    </View>
  );
};

const styles = StyleSheet.create({
  container: {
    flex: 1,
    padding: 20,
    alignItems: "center",
    justifyContent: "center",
    backgroundColor: "#fff",
  },
  title: {
    fontSize: 24,
    marginBottom: 20,
    fontWeight: "bold",
  },
  welcomeText: {
    fontSize: 18,
    marginBottom: 10,
  },
  buttonContainer: {
    marginVertical: 10,
    width: "80%",
  },
});

export default Homepage;