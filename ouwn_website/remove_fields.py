import firebase_admin
from firebase_admin import credentials, firestore

# Initialize Firebase
cred = credentials.Certificate("C:/MAMP/htdocs/ouwn_website/serviceAccountKey.json")  # 👈 use your JSON key
firebase_admin.initialize_app(cred)
db = firestore.client()

# Reference the "ouwn" collection (HealthCareP documents are inside it)
collection_ref = db.collection("ouwn")

# Get all documents that belong to HealthCareP
docs = collection_ref.stream()

removed_count = 0

for doc in docs:
    data = doc.to_dict()
    # Only process HealthCareP documents
    if "HealthCareP_" in doc.id:
        updates = {}
        if "email_token" in data:
            updates["email_token"] = firestore.DELETE_FIELD
        if "email_token_expires" in data:
            updates["email_token_expires"] = firestore.DELETE_FIELD

        if updates:
            doc.reference.update(updates)
            removed_count += 1
            print(f"🧽 Cleaned {doc.id}")

print(f"✅ Done! Removed fields from {removed_count} HealthCareP documents.")
