import firebase_admin
from firebase_admin import credentials, firestore

cred = credentials.Certificate("C:\MAMP\htdocs\ouwn_website\serviceAccountKey.json")  # Your Firebase key
firebase_admin.initialize_app(cred)
db = firestore.client()
