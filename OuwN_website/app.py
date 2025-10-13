from flask import Flask, render_template, request, redirect, url_for, flash
from flask_mail import Mail, Message
import secrets

app = Flask(__name__)
app.secret_key = 'secret-key'  # use a strong secret in production

@app.route('/signup')
def signup():
    return render_template('signup.html')

@app.route('/dashboard')
def patient_dashboard():
    return render_template('dashboard.html')

@app.route('/add_patient')
def add_patient():
    return render_template('AddPatient.html')

@app.route('/write_notes')
def write_notes():
    return render_template('MedicalNotes.html')

@app.route('/doctor_profile')
def doctor_profile():
    return render_template('profile.html')


# ---- Email configuration ----
app.config['MAIL_SERVER'] = 'smtp.gmail.com'
app.config['MAIL_PORT'] = 587
app.config['MAIL_USE_TLS'] = True
app.config['MAIL_USERNAME'] = 'ouwnsystem@gmail.com'  # your email
app.config['MAIL_PASSWORD'] = 'hekwyotvhhijigbo'      # app-specific password

mail = Mail(app)

# ---- Dummy user data ----
users = {'test@example.com': {'password': '1234'}}

# ---- Token storage ----
reset_tokens = {}

# ---- Home page ----
@app.route('/')
def home():
    return render_template('homePage.html')

# ---- Login page ----
@app.route('/login', methods=['GET', 'POST'])
def login():
    if request.method == 'POST':
        # Handle login logic here
        return redirect(url_for('login'))
    return render_template('login.html')


# ---- Forgot password page ----
@app.route('/reset_password', methods=['GET', 'POST'])
def reset_password():
    if request.method == 'POST':
        email = request.form.get('email')
        token = secrets.token_urlsafe(16)
        reset_tokens[token] = email
        reset_link = url_for('reset_token', token=token, _external=True)

        # Styled HTML email
        msg = Message('OuwN Password Reset Request',
                      sender='ouwnsystem@gmail.com',
                      recipients=[email])
        msg.html = f"""
<html>
  <body style="font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; color: #2d004d; background: #f4eefc; padding: 20px;">
    <div style="max-width: 600px; margin: auto; background: #fff; border-radius: 10px; padding: 30px; box-shadow: 0 5px 15px rgba(0,0,0,0.1);">
      <h2 style="color: #9975C1; text-align: center;">OuwN Password Reset</h2>
      <p>Hello,</p>
      <p>You requested to reset your password. Click the button below to reset it:</p>
      <div style="text-align: center; margin: 30px 0;">
        <a href="{reset_link}" style="background: #9975C1; color: white; padding: 12px 25px; text-decoration: none; border-radius: 25px; font-weight: bold;">
          Reset Password
        </a>
      </div>
      <p>If you didn't request this, you can ignore this email.</p>
      <p>Thanks,<br><strong>OuwN Team</strong></p>
    </div>
  </body>
</html>
"""
        mail.send(msg)
        flash('If your email exists, a reset link has been sent!', 'info')
        return redirect(url_for('login'))

    return render_template('reset_password.html')

# ---- Reset password via token ----
@app.route('/reset/<token>', methods=['GET', 'POST'])
def reset_token(token):
    email = reset_tokens.get(token)
    if not email:
        flash('Invalid or expired link!', 'danger')
        return redirect(url_for('reset_password'))

    if request.method == 'POST':
        password = request.form.get('password')
        confirm_password = request.form.get('confirm_password')
        if password != confirm_password:
            flash("Passwords do not match!", "danger")
            return render_template('reset_token.html')

        users[email]['password'] = password
        del reset_tokens[token]
        flash('Password updated successfully!', 'success')
        return redirect(url_for('login'))

    return render_template('reset_token.html')


if __name__ == '__main__':
    app.run(debug=True)
