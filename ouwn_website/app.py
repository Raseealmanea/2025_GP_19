from flask import Flask, render_template
from routes.Authentication import auth_bp  # import your blueprint

app = Flask(__name__)
app.secret_key = r"C:/MAMP/htdocs/ouwn_website/serviceAccountKey.json"

# Register blueprint
app.register_blueprint(auth_bp, url_prefix="/auth")

# Home page route
@app.route("/")
def home():
    return render_template("homePage.html")

if __name__ == "__main__":
    app.run(debug=True)
