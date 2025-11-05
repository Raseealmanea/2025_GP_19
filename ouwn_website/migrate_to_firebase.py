import mysql.connector
import firebase_admin
from firebase_admin import credentials, firestore
from datetime import date, datetime

# -----------------------------
# 1️⃣ Connect to MySQL
# -----------------------------
print("🔌 Connecting to MySQL...")
conn = mysql.connector.connect(
    host="localhost",
    user="root",
    password="root",
    database="OuwnDB",
    port=8889  # Change if needed
)
cursor = conn.cursor(dictionary=True)
print("✅ MySQL connection successful.")

# -----------------------------
# 2️⃣ Initialize Firebase
# -----------------------------
print("🚀 Initializing Firebase...")
cred = credentials.Certificate("C:/MAMP/htdocs/ouwn_website/serviceAccountKey.json")
firebase_admin.initialize_app(cred)
db = firestore.client()
print("✅ Firebase initialized successfully.")

# Helper function to convert date/datetime objects to strings
def clean_data(row):
    """Convert all date/datetime objects in a row to strings."""
    for key, value in row.items():
        if isinstance(value, (date, datetime)):
            row[key] = value.isoformat()
    return row

# -----------------------------
# 3️⃣ Upload HealthCareP Table
# -----------------------------
print("⬆️ Uploading HealthCareP data...")
cursor.execute("SELECT * FROM HealthCareP")
data = cursor.fetchall()

for row in data:
    row = clean_data(row)
    user_id = row["UserID"]
    db.collection("ouwn").document("HealthCareP_" + str(user_id)).set(row)

print(f"✅ Uploaded {len(data)} HealthCareP records.")

# -----------------------------
# 4️⃣ Upload Patient Table
# -----------------------------
print("⬆️ Uploading Patient data...")
cursor.execute("SELECT * FROM Patient")
patients = cursor.fetchall()

for row in patients:
    row = clean_data(row)
    patient_id = row["ID"]
    db.collection("ouwn").document("Patient_" + str(patient_id)).set(row)

print(f"✅ Uploaded {len(patients)} Patient records.")

# -----------------------------
# 5️⃣ Upload MedicalNote Table
# -----------------------------
print("⬆️ Uploading MedicalNote data...")
cursor.execute("SELECT * FROM MedicalNote")
notes = cursor.fetchall()

for row in notes:
    row = clean_data(row)
    note_id = row["id"]
    db.collection("ouwn").document("MedicalNote_" + str(note_id)).set(row)

print(f"✅ Uploaded {len(notes)} MedicalNote records.")

# -----------------------------
# ✅ Done!
# -----------------------------
cursor.close()
conn.close()
print("🎉 All data migrated to Firebase Firestore (collection: 'ouwn').")
