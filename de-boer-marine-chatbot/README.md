# De Boer Marine AI Chatbot

A modern AI chatbot application built with Laravel that connects to n8n workflows for intelligent responses. The chatbot features De Boer Marine's purple-themed dark mode design with a clean, professional interface for maritime industry users.

## Features

- **Modern Chat Interface**: Clean, responsive design with real-time messaging
- **n8n Integration**: Seamless connection to n8n workflows via webhooks
- **Conversation History**: Save and search through past conversations
- **User Authentication**: Secure user registration and login system
- **Dark Purple Theme**: Professional De Boer Marine branding with purple color scheme
- **Responsive Design**: Works perfectly on desktop and mobile devices
- **Real-time Status**: Connection status indicators for n8n webhook

## Technology Stack

- **Backend**: Laravel 10.x
- **Frontend**: Laravel Blade, TailwindCSS, Alpine.js
- **Database**: SQLite (default), MySQL/PostgreSQL (production)
- **Authentication**: Laravel Breeze
- **External Integration**: n8n Webhook API

## Installation

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd de-boer-marine-chatbot
   ```

2. **Install dependencies**
   ```bash
   composer install
   npm install
   ```

3. **Environment setup**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Database setup**
   ```bash
   php artisan migrate
   php artisan db:seed
   ```

5. **Build assets**
   ```bash
   npm run build
   ```

6. **Start the development server**
   ```bash
   php artisan serve
   ```

The application will be available at `http://localhost:8000`

## Configuration

### n8n Webhook Setup

1. Navigate to the Settings page after logging in
2. Enter your n8n webhook URL (e.g., `https://your-n8n-instance.com/webhook/your-webhook-id`)
3. Optionally add an API key if your webhook requires authentication
4. Test the connection to ensure it's working properly
5. Save the configuration

### n8n Workflow Requirements

Your n8n workflow should:
- Accept POST requests at the configured webhook URL
- Expect JSON payload with `message`, `conversation_id`, and `timestamp` fields
- Return JSON response with at least a `response` field
- Optionally return a `conversation_id` to maintain conversation context

Example webhook payload:
```json
{
  "message": "Hello, how are you?",
  "conversation_id": "123",
  "timestamp": "2026-01-06T12:00:00Z"
}
```

Example response:
```json
{
  "response": "I'm doing well, thank you! How can I assist you today?",
  "conversation_id": "123"
}
```

## Usage

### For Users

1. **Register/Login**: Create an account or log in to access the chatbot
2. **Start Chatting**: Navigate to the Chat page and start sending messages
3. **View History**: Browse your conversation history in the History page
4. **Search Conversations**: Use the search functionality to find specific conversations

### For Administrators

1. **Configure n8n**: Set up the webhook URL in the Settings page
2. **Test Connection**: Use the built-in connection test to verify n8n integration
3. **Monitor Usage**: Check conversation logs and system status

## API Endpoints

### Chat API
- `POST /api/chat/send` - Send a message to the chatbot
- `GET /api/chat/history` - Get conversation history
- `GET /api/chat/conversation/{id}` - Get specific conversation

### Settings API
- `POST /api/settings/webhook` - Update n8n webhook configuration
- `POST /api/settings/test-webhook` - Test n8n webhook connection

### History API
- `GET /api/history/conversations` - Get paginated conversation list
- `DELETE /api/history/conversation/{id}` - Delete a conversation

## File Structure

```
de-boer-marine-chatbot/
├── app/
│   ├── Http/
│   │   └── Controllers/
│   │       ├── ChatController.php
│   │       ├── SettingsController.php
│   │       └── HistoryController.php
│   ├── Models/
│   │   ├── User.php
│   │   ├── Conversation.php
│   │   ├── Message.php
│   │   └── N8nConfig.php
│   └── Services/
│       └── N8nService.php
├── resources/
│   └── views/
│       ├── chat/
│       │   └── index.blade.php
│       ├── settings/
│       │   └── index.blade.php
│       ├── history/
│       │   └── index.blade.php
│       └── layouts/
├── routes/
│   ├── web.php
│   └── api.php
└── database/
    └── migrations/
```

## Customization

### Styling

The application uses TailwindCSS with a custom purple color scheme. You can modify the colors in `tailwind.config.js`:

```javascript
colors: {
    purple: {
        400: '#9333EA',  // Light purple
        500: '#7C3AED',  // Medium purple
        600: '#6B46C1',  // Primary purple (De Boer Marine brand)
        700: '#5B21B6',  // Dark purple
        800: '#4C1D95',  // Very dark purple
    },
}
```

### Adding New Features

1. **Backend**: Create new controllers, models, and services as needed
2. **Frontend**: Add new Blade views with the existing dark theme
3. **API**: Extend the API routes in `routes/api.php`
4. **Database**: Create new migrations for additional data structures

## Security

- All API endpoints are protected with CSRF tokens
- User authentication is required for history and settings
- Input validation is implemented on all forms
- SQL injection protection through Laravel's ORM
- XSS protection through Blade's automatic escaping

## Troubleshooting

### n8n Connection Issues

1. **Check webhook URL**: Ensure the URL is correct and accessible
2. **Test connection**: Use the built-in connection test in Settings
3. **Check n8n logs**: Look at your n8n instance logs for errors
4. **Verify network**: Ensure your server can reach the n8n instance

### Database Issues

1. **Run migrations**: `php artisan migrate`
2. **Check database file**: Ensure SQLite file exists in `database/` directory
3. **Clear cache**: `php artisan cache:clear`

### Asset Issues

1. **Rebuild assets**: `npm run build`
2. **Clear browser cache**: Hard refresh the page
3. **Check file permissions**: Ensure web server can read asset files

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test thoroughly
5. Submit a pull request

## License

This project is proprietary software for De Boer Marine Urk.

## Support

For support and questions, please contact the development team or create an issue in the repository.