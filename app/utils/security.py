import jwt
from datetime import datetime, timedelta
from flask import request, abort, jsonify
from app.config import SECRET_KEY

def create_token(data: dict, expires_in=60):
    payload = data.copy()
    payload["exp"] = datetime.utcnow() + timedelta(minutes=expires_in)
    return jwt.encode(payload, SECRET_KEY, algorithm="HS256")

def verify_token():
    auth_header = request.headers.get('Authorization')
    if not auth_header:
        abort(401, description="Authorization header missing")
    
    try:
        scheme, token = auth_header.split()
        if scheme.lower() != 'bearer':
            abort(401, description="Invalid authentication scheme")
    except ValueError:
        abort(401, description="Invalid Authorization header format")

    try:
        payload = jwt.decode(token, SECRET_KEY, algorithms=["HS256"])
        return payload
    except jwt.ExpiredSignatureError:
        abort(440, description="Token expired") # Using 440 for Login Timeout
    except jwt.InvalidTokenError:
        abort(401, description="Invalid token")