class User {
  final String username, name;
  final String password;
  final int userId;

  User({required this.username, required this.name, required this.password, required this.userId});

  factory User.fromJson(Map<String, dynamic> json) {
    return User(
      username: json['username'],
      name: json['name'],
      password: '', 
      userId: json['userId'] as int,
    );
  }
    Map<String, dynamic> toJson() {
    return {
      'username': username,
      'name': name,
      'password': '', 
      'userId': userId, 
    };
  }
}