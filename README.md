# The Promenade

The Promenade is a social media platform built with PHP that allows users to share posts, interact through comments and likes, and connect with other users.

## Features

- **User Authentication**
  - User registration and login
  - Secure session management
  - Profile management

- **Social Features**
  - Create and view posts
  - Comment on posts
  - Like posts
  - Follow other users
  - User profiles

- **Content Management**
  - Image upload support
  - Post expiration system
  - Rich post viewing experience

- **Search Functionality**
  - Search for users
  - Search for posts
  - Real-time post and comment counts

## Project Structure

```
The Promenade/
├── config/
│   └── database.php
├── includes/
│   ├── db.php
│   ├── functions.php
│   ├── auth.php
│   └── session.php
├── uploads/
│   └── images/
├── assets/
│   ├── css/
│   │   └── style.css
│   └── js/
│       └── script.js
├── index.php
├── login.php
├── register.php
├── logout.php
├── profile.php
├── edit_profile.php
├── post.php
├── view_post.php
├── follow.php
├── like.php
├── comment.php
└── dashboard.php
```

## Requirements

- PHP 7.0 or higher
- MySQL 5.7 or higher
- Apache/Nginx web server
- mod_rewrite enabled (for Apache)

## Installation

1. Clone the repository:
```bash
git clone https://github.com/yourusername/the-promenade.git
```

2. Configure your database settings in `config/database.php`

3. Import the database schema:
   - Use the SQL files provided in the `database` directory

4. Configure your web server to point to the project directory

5. Ensure the `uploads` directory has write permissions:
```bash
chmod 755 uploads/images
```

## Usage

1. Register a new account at `/register.php`
2. Log in with your credentials
3. Start sharing posts and connecting with other users
4. Use the search functionality to find users and posts
5. View and interact with posts through comments and likes

## Security Features

- Password hashing
- SQL injection prevention through prepared statements
- XSS protection with output escaping
- CSRF protection
- Secure session management

## Advanced Features

- **Notification System**
  - Real-time notifications for likes, comments, and follows
  - Email notifications for important account activities

- **Analytics Dashboard**
  - Track user engagement metrics
  - Post reach and interaction statistics
  - Personal growth insights

- **Content Moderation**
  - Automated content filtering
  - User reporting system
  - Admin moderation panel

## Performance Optimization

The Promenade is optimized for performance through:
- Lazy loading of images
- Database query optimization
- Content caching mechanisms
- Asynchronous loading of non-critical resources

## Mobile Responsiveness

The platform is fully responsive and provides an optimal viewing experience across a wide range of devices (desktops, tablets, and smartphones) through:
- Responsive CSS design
- Mobile-friendly navigation
- Touch-optimized interactions

## API Documentation

The Promenade offers a RESTful API for developers wishing to extend functionality or integrate with other services. Documentation is available in the `docs/api` directory.

## Troubleshooting

Common issues and their solutions:
- **Upload errors**: Check directory permissions and PHP upload limits
- **Database connection failures**: Verify credentials in `config/database.php`
- **White screen errors**: Enable error reporting in your PHP configuration
- **Session issues**: Check cookie settings and session timeout configurations

## Roadmap

Future features planned for The Promenade:
- Direct messaging system
- Stories/ephemeral content
- Group functionality
- Enhanced media support (video, audio)
- Integration with third-party services

## Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

## Acknowledgments

- [Font Awesome](https://fontawesome.com/) for icons
- [Bootstrap](https://getbootstrap.com/) for front-end components
- [PHPMailer](https://github.com/PHPMailer/PHPMailer) for email functionality
- All contributors who have helped shape and improve this project

## Contact

For support or inquiries, please contact us at:
- Email: erirjorge@gmail.com
- Instagram: [@tachi_bana1223](https://www.instagram.com/tachi_bana1223/)
- GitHub Issues: [Report a bug](https://github.com/EriRJorge/the-promenade/issues)

---

**The Promenade** - Connect, Share, Explore