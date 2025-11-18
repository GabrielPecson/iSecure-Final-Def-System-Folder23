import pymysql
import os
from dotenv import load_dotenv

# Load environment variables from a .env file
load_dotenv()

def get_db_connection():
    """
    Creates and returns a new database connection.
    Reads configuration from environment variables for security and flexibility.
    """
    try:
        connection = pymysql.connect(
            host=os.environ.get('DB_HOST', 'localhost'),
            user=os.environ.get('DB_USER', 'root'),
            password=os.environ.get('DB_PASSWORD', ''),
            db=os.environ.get('DB_NAME', 'isecure'), # Use 'db' instead of 'database' for pymysql
            cursorclass=pymysql.cursors.DictCursor
        )
        return connection
    except pymysql.MySQLError as e:
        print(f"Error connecting to MySQL database: {e}")
        # In a background thread, it's better to raise the exception
        # so it can be caught and logged.
        raise e