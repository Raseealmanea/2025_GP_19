from flask import Blueprint, request, render_template, redirect, url_for, flash, session
from werkzeug.security import check_password_hash
from firebase.Initialization import db  # Ensure this points to your Firebase init file
import re
from flask import jsonify

auth_bp = Blueprint("Authentication", __name__)

@auth_bp.route("/login", methods=["GET", "POST"])
def login():
    entered_username = ""
    if request.method == "POST":
        username = request.form.get("username", "").strip()
        password = request.form.get("password", "")

        entered_username = username  # Keep input for form refill

        if not username or not password:
            flash("Please check your information and try again.", "error")
            return redirect(url_for("Authentication.login"))

        # Fetch user document from Firebase
        doc_ref = db.collection("ouwn").document(f"HealthCareP_{username}")
        doc = doc_ref.get()

        if not doc.exists:
            flash("Username or Password is invalid.", "error")
            return redirect(url_for("Authentication.login"))

        user = doc.to_dict()

        # Verify password
        if not check_password_hash(user.get("Password", ""), password):
            flash("Username or Password is invalid.", "error")
            return redirect(url_for("Authentication.login"))

        # Success: store user info in session
        session["user_id"] = user.get("UserID")
        session["user_name"] = user.get("Name") or user.get("UserID")
        session["user_email"] = user.get("Email", "")

        flash("✅ Logged in successfully. Welcome back!", "success")
        return redirect(url_for("patient_dashboard"))  # Adjust to your dashboard route

    return render_template("login.html", entered_username=entered_username)


@auth_bp.route("/signup", methods=["GET", "POST"])
def signup():
    entered = {"first_name": "", "last_name": "", "username": "", "email": ""}
    
    if request.method == "POST":
        first  = request.form.get("first_name", "").strip()
        last   = request.form.get("last_name", "").strip()
        username = request.form.get("username", "").strip()
        email  = request.form.get("email", "").strip()
        password = request.form.get("password", "")

        # Keep values for form refill
        entered.update({"first_name": first, "last_name": last, "username": username, "email": email})

        # Basic validation
        if not all([first, last, username, email, password]):
            flash("All fields are required.", "error")
            return render_template("signup.html", entered=entered)

        if len(password) < 8 or not any(c.isupper() for c in password) \
            or not any(c.islower() for c in password) \
            or not any(c.isdigit() for c in password) \
            or not any(not c.isalnum() for c in password):
            flash("Password must meet all requirements.", "error")
            return render_template("signup.html", entered=entered)

        # Check duplicates in Firebase
        user_doc = db.collection("ouwn").document(f"HealthCareP_{username}").get()
        email_docs = db.collection("ouwn").where("Email", "==", email).get()

        if user_doc.exists or len(email_docs) > 0:
            flash("Username or Email already exists.", "error")
            return render_template("signup.html", entered=entered)

        # Hash password
        hashed_pw = generate_password_hash(password)

        # Save to Firebase
        db.collection("ouwn").document(f"HealthCareP_{username}").set({
            "UserID": username,
            "Email": email,
            "Password": hashed_pw,
            "Name": f"{first} {last}",
            "email_confirmed": 0
        })

        flash("✅ Account created! Please check your email to confirm your account.", "success")
        return redirect(url_for("Authentication.login"))

    return render_template("signup.html", entered=entered)

@auth_bp.route("/check", methods=["GET"])
def check_field():
    """
    AJAX endpoint to check username or email availability.
    GET params: ?field=username&value=XXX
    """
    field = request.args.get("field", "")
    value = request.args.get("value", "").strip()
    result = {"ok": True, "exists": False, "valid": True}

    try:
        if field == "username":
            # Valid username pattern
            result["valid"] = bool(re.fullmatch(r"[A-Za-z0-9_.-]{3,32}", value))
            if result["valid"]:
                doc = db.collection("ouwn").document(f"HealthCareP_{value}").get()
                result["exists"] = doc.exists
        elif field == "email":
            result["valid"] = "@" in value and "." in value  # basic email check
            if result["valid"]:
                docs = db.collection("ouwn").where("Email", "==", value).get()
                result["exists"] = len(docs) > 0
        else:
            result = {"ok": False}
    except Exception as e:
        result = {"ok": False}
        print("AJAX error:", e)

    return jsonify(result)