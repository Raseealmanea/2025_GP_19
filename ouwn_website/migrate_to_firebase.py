import firebase_admin
from firebase_admin import credentials, firestore
from datetime import datetime
import uuid

# -----------------------------
# 1️⃣ Connect to Firestore
# -----------------------------
cred = credentials.Certificate("C:/MAMP/htdocs/ouwn_website/serviceAccountKey.json")
firebase_admin.initialize_app(cred)
db = firestore.client()
print("✅ Connected to Firestore")


# -----------------------------
# 2️⃣ Add Doctors (English names)
# -----------------------------
doctors = [
    {
        "UserID": "DrFaisal",
        "Email": "dr.faisal@gmail.com",
        "Name": "Dr Faisal Alotaibi",
        "Password": "$2b$12$ZnzmdbV5T5fI/jp38zpBquT6.cxI9te1A/cyJE08V6WDxvXpToAAu",
        "email_confirmed": 1
    },
    {
        "UserID": "DrAhmed",
        "Email": "dr.ahmed@gmail.com",
        "Name": "Dr Ahmed Alshammari",
        "Password": "$2b$12$G7fXbW7d8/2kO/r8ZkHpruZnIq.4OIk1lKZK8dJrS2OxP1T8M1fKe",
        "email_confirmed": 1
    }
]

for doc in doctors:
    db.collection("HealthCareP").document(doc["UserID"]).set(doc)

print("👨‍⚕️ Doctors added successfully.")


# -----------------------------
# 3️⃣ Add Patient (with CreatedBy)
# -----------------------------
def add_patient(patient_id, full_name, dob, address, phone, gender, blood_type, doctor_id):
    patient_data = {
        "FullName": full_name,
        "DOB": dob.strftime("%Y-%m-%d"),
        "Address": address,
        "Phone": phone,
        "Gender": gender,
        "BloodType": blood_type,
        "CreatedBy": doctor_id        # ⭐ doctor who added this patient
    }
    db.collection("Patients").document(patient_id).set(patient_data)
    print(f"🧍 Patient added: {full_name} (CreatedBy: {doctor_id})")


# -----------------------------
# 4️⃣ Add Medical Note + ICD codes (array)
# -----------------------------
def add_medical_note(patient_id, note_text, icd_codes, doctor_id):
    """
    icd_codes should be a list → ["R51", "K35"]
    """

    patient_ref = db.collection("Patients").document(patient_id)

    # Generate note ID
    note_id = "note_id_" + uuid.uuid4().hex[:8]

    note_ref = patient_ref.collection("MedicalNotes").document(note_id)
    note_ref.set({
        "NoteID": note_id,
        "Note": note_text,
        "CreatedDate": datetime.now(),
        "CreatedBy": doctor_id
    })

    # ICD code doc ID
    icd_id = "icdcode_id_" + uuid.uuid4().hex[:8]

    note_ref.collection("ICDCode").document(icd_id).set({
        "ICD_ID": icd_id,
        "Adjusted": icd_codes,        # ⭐ ALL selected codes in one array
        "Predicted": [],
        "AdjustedBy": doctor_id,
        "AdjustedAt": datetime.now()
    })

    print(f"🩺 Note added for {patient_id} | Codes: {icd_codes}")


# -----------------------------
# 5️⃣ Example Data
# -----------------------------
add_patient("1111111111", "Noura Alsubaie", datetime(1990, 5, 14),
            "Riyadh", "0501234567", "F", "O+", "DrFaisal")

add_patient("2222222222", "Mohammed Alotaibi", datetime(1985, 7, 20),
            "Jeddah", "0509876543", "M", "O-", "DrAhmed")

add_medical_note("1111111111", "Patient reports mild headache.",
                 ["R51", "G44.1"], "DrFaisal")

add_medical_note("1111111111", "Patient has chest pain.",
                 ["I20.9"], "DrAhmed")

add_medical_note("2222222222", "Patient reports dizziness.",
                 ["R42"], "DrFaisal")
