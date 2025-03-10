class User {
  final String username;
  final String password;
  final int userId;

  User({required this.username, required this.password, required this.userId});

  factory User.fromJson(Map<String, dynamic> json) {
    return User(
      username: json['username'],
      password: '', 
      userId: json['userId'] as int,
    );
  }
    Map<String, dynamic> toJson() {
    return {
      'username': username,
      'password': '', 
      'userId': userId, 
    };
  }
}