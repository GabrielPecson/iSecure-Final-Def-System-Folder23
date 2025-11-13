# iSecure - Deployment Guide for Hostinger VPS

This guide outlines the steps to deploy the iSecure Flask application and its PHP frontend on a Hostinger Virtual Private Server (VPS). This approach uses a production-ready WSGI server (Gunicorn) behind a reverse proxy (Nginx) for optimal performance and security.

## 1. Prepare Your Flask Application

Before deployment, ensure your Flask application is ready for a production environment.

**Disable Debug Mode:**
Locate `app/main.py` and change the `debug` flag to `False`. Running with `debug=True` in production is a severe security risk.

```python
if __name__ == '__main__':
    app.run(host='localhost', port=8000, debug=False) # Change to False
```

## 2. Server Setup (SSH into your Hostinger VPS)

Connect to your VPS using SSH. The following steps assume an Ubuntu/Debian-based Linux distribution. Adjust package names if you are using a different OS.

### 2.1 Update System & Install Core Packages

```bash
sudo apt update && sudo apt upgrade -y
sudo apt install python3 python3-pip python3-venv nginx -y
```

### 2.2 Install OpenCV Dependencies

For `cv2` (OpenCV) to function correctly in a headless server environment, you might need to install additional display-related libraries, even if you don't have a GUI. These help with image processing functionalities.

```bash
# Essential for many OpenCV functions, especially if dealing with images
sudo apt install libgl1-mesa-glx -y
sudo apt install libsm6 libxext6 libxrender-dev -y
```
*(Verify these specific packages are correct for your exact OS version if issues arise)*

### 2.3 Upload Your Project

Transfer your entire project folder (`iSecure-Final-Def-System-Folder`) to your VPS. A common location is `/var/www/html/` or `/home/your_username/`.

For example, if you upload it to `/var/www/html/`:
```
/var/www/html/iSecure-Final-Def-System-Folder/
├── app/
├── php/
├── public/
├── scripts/
├── ...
```

### 2.4 Set Up Python Virtual Environment & Dependencies

Navigate to your project root directory on the VPS (e.g., `/var/www/html/iSecure-Final-Def-System-Folder`).

```bash
cd /var/www/html/iSecure-Final-Def-System-Folder
python3 -m venv venv
source venv/bin/activate
pip install -r app/requirements.txt
pip install gunicorn
deactivate
```

## 3. Configure Gunicorn (WSGI Server)

Gunicorn will run your Flask application.

### 3.1 Create a Gunicorn Service File

Create a `systemd` service file to manage Gunicorn. This ensures your application starts on boot and restarts if it crashes.

```bash
sudo nano /etc/systemd/system/isecure.service
```

Paste the following content, *replacing `your_username` with your actual SSH username on the VPS, and `/path/to/your/project/` with the absolute path to `iSecure-Final-Def-System-Folder`*.

```ini
[Unit]
Description=Gunicorn instance for iSecure Flask App
After=network.target

[Service]
User=your_username
Group=www-data # Or the group Nginx runs under, usually www-data
WorkingDirectory=/path/to/your/project/iSecure-Final-Def-System-Folder
Environment="PATH=/path/to/your/project/iSecure-Final-Def-System-Folder/venv/bin"
# --- Important: Set your Mindee API Key as an Environment Variable ---
Environment="MINDEE_API_KEY=YOUR_MINDEE_API_KEY_HERE"
ExecStart=gunicorn -w 4 -b 127.0.0.1:8000 app.main:app
Restart=always

[Install]
WantedBy=multi-user.target
```
**Important:**
*   Replace `YOUR_MINDEE_API_KEY_HERE` with your actual Mindee API key. This is the secure way to provide it to your Flask app.
*   The `Environment="PATH=..."` line ensures `gunicorn` from your virtual environment is used. Double-check the absolute path.

Save and close the file (Ctrl+X, Y, Enter for Nano).

### 3.2 Enable and Start Gunicorn Service

```bash
sudo systemctl daemon-reload
sudo systemctl start isecure
sudo systemctl enable isecure
```

You can check the status with `sudo systemctl status isecure`.

## 4. Configure Nginx (Reverse Proxy)

Nginx will serve your PHP frontend, static files, and proxy requests to your Flask backend.

### 4.1 Remove Default Nginx Configuration

```bash
sudo rm /etc/nginx/sites-enabled/default
```

### 4.2 Create Nginx Configuration for iSecure

```bash
sudo nano /etc/nginx/sites-available/isecure
```

Paste the following content, *replacing `yourdomain.com` with your actual domain or VPS IP, and `/path/to/your/project/` with the absolute path to `iSecure-Final-Def-System-Folder`*.

```nginx
server {
    listen 80;
    server_name yourdomain.com; # Replace with your domain or VPS IP

    root /path/to/your/project/iSecure-Final-Def-System-Folder/php/routes; # Your PHP frontend root
    index index.php index.html;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # Proxy API requests to Flask/Gunicorn
    location /api/ {
        proxy_pass http://127.0.0.1:8000/;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        # Required for camera streaming, prevent buffering
        proxy_buffering off;
        proxy_cache off;
        proxy_redirect off;
        proxy_set_header Connection ""; # Disable Connection header for HTTP/1.1 keep-alive
    }

    # Process PHP files
    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock; # Adjust PHP version (e.g., php7.4-fpm.sock or php8.2-fpm.sock)
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    # Serve static assets directly from their folders
    location /images/ {
        alias /path/to/your/project/iSecure-Final-Def-System-Folder/images/;
        expires 30d;
        add_header Cache-Control "public, no-transform";
    }
    location /scripts/ {
        alias /path/to/your/project/iSecure-Final-Def-System-Folder/scripts/;
        expires 30d;
        add_header Cache-Control "public, no-transform";
    }
    location /stylesheet/ {
        alias /path/to/your/project/iSecure-Final-Def-System-Folder/stylesheet/;
        expires 30d;
        add_header Cache-Control "public, no-transform";
    }
    location /public/ {
        alias /path/to/your/project/iSecure-Final-Def-System-Folder/public/;
        expires 30d;
        add_header Cache-Control "public, no-transform";
    }
}
```
**Important:**
*   Adjust `root` to the correct path of your PHP `routes` directory.
*   Update `fastcgi_pass` to match your installed PHP-FPM socket (`php8.1-fpm.sock` is an example; check `ls /var/run/php/` for actual).
*   Correctly set `alias` for all your static content locations (`images/`, `scripts/`, `stylesheet/`, `public/`).

Save and close the file.

### 4.3 Enable Nginx Configuration & Restart

```bash
sudo ln -s /etc/nginx/sites-available/isecure /etc/nginx/sites-enabled/
sudo nginx -t # Test Nginx configuration for syntax errors
sudo systemctl restart nginx
```

## 5. Important Considerations for Camera Setup on VPS

This Flask application relies heavily on `cv2.VideoCapture` to access camera feeds for facial and vehicle recognition. Deploying this on a remote VPS introduces specific challenges compared to running it on a local machine.

### 5.1 Physical Webcams Connected to the VPS (Rare for Remote Servers)
*   **Direct Access is Unlikely:** For a typical remote VPS, connecting physical webcams is usually not feasible or practical unless the VPS is a local machine configured as a server.
*   **Device Permissions:** If, by any chance, you *do* have physical webcams directly connected, ensure the Linux user running your Gunicorn service (e.g., `your_username` or `www-data`) has read/write permissions to the camera devices, typically `/dev/video0`, `/dev/video1`, etc. You can check permissions with `ls -l /dev/video*`. You might need to add the user to the `video` group: `sudo usermod -aG video your_username`.
*   **USB Passthrough (Virtualization):** If your VPS is a virtual machine and you're *trying* to pass through a USB webcam from the host, this is a complex virtualization setup and often unreliable.

### 5.2 CCTV/IP Cameras (Most Common for Remote VPS)
This is the most common and recommended approach for a remote server.

*   **Network Accessibility:** Your VPS must have direct network access to the CCTV/IP camera streams.
    *   **Public IP/Domain:** If your CCTV stream is exposed to the internet, ensure it's secured (authentication, limited VPN access) and accessible by the VPS's public IP.
    *   **Private Network/VPN:** Ideally, your CCTV cameras and VPS would be on the same private network or connected via a VPN to ensure security and low latency.
*   **Stream URLs (`rtsp://`, `http://`, `https://`):**
    *   `cv2.VideoCapture` can open various network streams using their URLs (e.g., `rtsp://user:pass@192.168.1.100/stream`).
    *   You will need to know the exact stream URL provided by your IP camera or NVR (Network Video Recorder). Consult your camera's documentation.
    *   Ensure the Flask application receives these correct URLs from the frontend via the `/camera/source` endpoint.
*   **Firewall Rules:** Ensure your VPS firewall (e.g., `ufw`) allows outgoing connections to the CCTV camera's IP and port. Conversely, if the cameras are behind a firewall, ensure that traffic from your VPS can reach them.
*   **Latency & Bandwidth:** Streaming live video over the internet can introduce latency and consume significant bandwidth. Optimize camera settings (resolution, frame rate) to balance quality with performance.
*   **Codecs and `ffmpeg`:** OpenCV relies on `ffmpeg` (or similar libraries) for decoding video streams. While `opencv-python` usually comes with pre-compiled `ffmpeg` support, if you encounter issues, you might need to install `ffmpeg` explicitly on your VPS: `sudo apt install ffmpeg -y`.

### 5.3 Defaulting to Webcam (When no CCTV is linked for a type)
*   **Facial Camera (`camera_facial`):** Defaults to webcam index `0`. If running on a remote VPS without a real webcam, this will likely fail to initialize and fall back to `DummyCamera`.
*   **Vehicle Camera (`camera_vehicle`):** Defaults to webcam index `1`. Similarly, this will likely fail on a remote VPS without physical webcams.

**Recommendation:** For a remote Hostinger VPS, plan to use **CCTV/IP camera URLs** exclusively. Ensure these URLs are robust and accessible from your VPS. The webcam fallback (`source=0` or `source=1`) will likely only work if a physical webcam is directly connected to the server or if you're running this on a local machine. If only CCTV is intended, you might consider removing the "webcam" option from the frontend dropdowns for clarity and to avoid user confusion on a remote server. You can also explicitly configure fixed IP camera URLs in `app/config.py` instead of the `0` or `1` index if dedicated webcams are not expected.

## 6. Access Your Application

After completing these steps, your Flask API should be running via Gunicorn, Nginx should handle your PHP frontend and static files, and all API requests should be correctly proxied.

You can then access your application via your configured domain or VPS IP in a web browser.
