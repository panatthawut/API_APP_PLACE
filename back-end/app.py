from fastapi import FastAPI, File, UploadFile, HTTPException, Request,Form
from fastapi.middleware.cors import CORSMiddleware
import joblib
import numpy as np
from tensorflow.keras.applications.efficientnet import EfficientNetB7, preprocess_input
from tensorflow.keras.models import Model
from tensorflow.keras.layers import GlobalAveragePooling2D
from tensorflow.keras.preprocessing.image import load_img, img_to_array
from PIL import Image
import io
import logging
import os
from sklearn.neighbors import NearestNeighbors
from pydantic import BaseModel
from sklearn.preprocessing import StandardScaler
from sklearn.metrics import accuracy_score, classification_report, confusion_matrix
from sklearn.model_selection import cross_val_score, train_test_split
from tensorflow.keras.preprocessing.image import ImageDataGenerator
logging.basicConfig(level=logging.INFO)

app = FastAPI()

# กำหนด CORS เพื่อให้สามารถเรียก API จากแอพ Flutter ได้
origins = [
    "http://localhost",
    "http://localhost:8000",
]

app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

# Path configurations
FEATURES_PATH = "models/features.npy"
MODEL_PATH = "models/knn_model.joblib"
TRAINED_MODEL_PATH = 'models/efficientnetb7_weights.weights.h5'
LABELS_PATH = "models/labels.npy"
INDEX_TO_CLASS_PATH = "models/index_to_class.joblib"
TRAIN_INDICES_TO_CLASS = "models/train_indices_to_class.joblib"
BASE_PATH = "C:/xampp/htdocs/api_php/"
FEATURES_NORMALIZED_PATH ="models/features_normalized.npy"
# โหลดโมเดลและ mapping
knn_model = NearestNeighbors(n_neighbors=5)
try:
    if os.path.exists(MODEL_PATH):
        knn_model = joblib.load(MODEL_PATH)
        logging.info("KNN model loaded successfully.")
except Exception as e:
    logging.error(f"Error loading KNN model: {e}")

index_to_class = {}
try:
    index_to_class = joblib.load('models/index_to_class.joblib')
    logging.info("Index to class mapping loaded successfully.")
except Exception as e:
    logging.error(f"Error loading index_to_class: {e}")


train_indices_to_class = {}
try:
    train_indices_to_class = joblib.load('models/train_indices_to_class.joblib')
    logging.info("Train indices to class mapping loaded successfully.")
except Exception as e:
    logging.error(f"Error loading train_indices_to_class: {e}")
# สร้างโครงสร้างของ EfficientNetB7
base_model = EfficientNetB7(weights=None, include_top=False, input_shape=(600, 600, 3))
x = base_model.output
x = GlobalAveragePooling2D()(x)
efficientnet_model = Model(inputs=base_model.input, outputs=x)

# โหลดค่าน้ำหนักเข้าไปในโมเดล
try:
    if os.path.exists(TRAINED_MODEL_PATH):
        efficientnet_model.load_weights(TRAINED_MODEL_PATH)
        logging.info("EfficientNet weights loaded successfully.")
except Exception as e:
    logging.error(f"Error loading EfficientNet weights: {e}")
def load_models():
    knn_model = NearestNeighbors(n_neighbors=5)
    try:
        if os.path.exists(MODEL_PATH):
            knn_model = joblib.load(MODEL_PATH)
            logging.info("KNN model loaded successfully.")
    except Exception as e:
        logging.error(f"Error loading KNN model: {e}")

    index_to_class = {}
    try:
        index_to_class = joblib.load('models/index_to_class.joblib')
        logging.info("Index to class mapping loaded successfully.")
    except Exception as e:
        logging.error(f"Error loading index_to_class: {e}")


    train_indices_to_class = {}
    try:
        train_indices_to_class = joblib.load('models/train_indices_to_class.joblib')
        logging.info("Train indices to class mapping loaded successfully.")
    except Exception as e:
        logging.error(f"Error loading train_indices_to_class: {e}")

    # สร้างโครงสร้างของ EfficientNetB7
    base_model = EfficientNetB7(weights=None, include_top=False, input_shape=(600, 600, 3))
    x = base_model.output
    x = GlobalAveragePooling2D()(x)
    efficientnet_model = Model(inputs=base_model.input, outputs=x)

    # โหลดค่าน้ำหนักเข้าไปในโมเดล
    try:
        if os.path.exists(TRAINED_MODEL_PATH):
            efficientnet_model.load_weights(TRAINED_MODEL_PATH)
            logging.info("EfficientNet weights loaded successfully.")
    except Exception as e:
        logging.error(f"Error loading EfficientNet weights: {e}")
load_models()
labels = np.load(LABELS_PATH)
features = np.load(FEATURES_PATH)
print('labels',labels)
print('index_to_class',index_to_class)
print('knn_model',knn_model)
print('features',features)
#print('train_indices_to_class',train_indices_to_class)
index_to_class = joblib.load(INDEX_TO_CLASS_PATH)
features = np.load(FEATURES_PATH)
logging.info(f"Loaded features with shape: {features.shape}")
logging.info(f"Loaded labels with shape: {labels.shape}")
def preprocess_image_from_bytes(image_bytes):
    img = Image.open(io.BytesIO(image_bytes)).convert('RGB')
    img = img.resize((600, 600))  # ขนาดที่โมเดลต้องการ
    img_array = np.array(img)
    img_array = preprocess_input(img_array)  # ใช้ preprocessing เดียวกับที่ใช้ในการเทรน
    img_array = np.expand_dims(img_array, axis=0)
    return img_array
import requests
from math import radians, sin, cos, sqrt, atan2
from math import log
# ฟังก์ชัน Haversine สำหรับคำนวณระยะทาง
def haversine(lat1, lon1, lat2, lon2):
    try:
        R = 6371.0  # รัศมีของโลก (กิโลเมตร)
        dlat = radians(lat2 - lat1)
        dlon = radians(lon2 - lon1)
        a = sin(dlat / 2)**2 + cos(radians(lat1)) * cos(radians(lat2)) * sin(dlon / 2)**2
        c = 2 * atan2(sqrt(a), sqrt(1 - a))
        return R * c  # ระยะทางเป็นกิโลเมตร
    except Exception as e:
        logging.error(f"Error in haversine calculation: {e}")
        return float("inf")  # ระยะทางไม่สามารถคำนวณได้

@app.post("/predict_gallery")
async def predict(
    image: UploadFile = File(...),
    latitude: float = Form(...),
    longitude: float = Form(...),
):
    logging.info(f"Received latitude: {latitude}, longitude: {longitude}")

    if not image:
        raise HTTPException(status_code=400, detail="No image provided")

    try:
        image_bytes = await image.read()
        processed_image = preprocess_image_from_bytes(image_bytes)
        logging.info("Image preprocessing completed.")

        # สกัดฟีเจอร์จาก EfficientNet
        features = efficientnet_model.predict(processed_image)
        features = features.flatten()
        logging.info(f"Extracted features shape: {features.shape}")

        # ใช้ KNN ในการหาค่า 50 เพื่อนบ้านที่ใกล้ที่สุด
        distances, indices = knn_model.kneighbors([features], n_neighbors=50)
        logging.info(f"Distances: {distances}")
        logging.info(f"Indices: {indices}")

        nearest_locations = []
        class_similarity_map = {}

        for index in indices[0]:
            location = train_indices_to_class.get(index, "Unknown")
            similarity = round((1 - distances[0][np.where(indices[0] == index)[0][0]]) * 100, 2)

            if location not in class_similarity_map:
                class_similarity_map[location] = similarity
            else:
                class_similarity_map[location] = max(class_similarity_map[location], similarity)

        sorted_locations = sorted(class_similarity_map.items(), key=lambda x: x[1], reverse=True)

        for location, similarity in sorted_locations:
            if location not in [loc["location"] for loc in nearest_locations]:
                nearest_locations.append({"location": location, "similarity": similarity})
            if len(nearest_locations) == 5:
                break
        logging.info(f"All locations with scores: {class_similarity_map}")

        logging.info(f"Nearest Locations: {nearest_locations}")

        return {"nearest_locations": nearest_locations}
    except Exception as e:
        logging.error(f"Error during prediction: {e}")
        raise HTTPException(status_code=500, detail=str(e))
@app.post("/predict_cameraa")
async def predict(
    image: UploadFile = File(...),
    latitude: float = Form(...),
    longitude: float = Form(...),
):
    max_distance = 50  # ระยะทางสูงสุดที่กำหนดไว้ในโค้ด (หน่วย: กิโลเมตร)
    logging.info(f"Received latitude: {latitude}, longitude: {longitude}, max_distance: {max_distance}")

    if not image:
        raise HTTPException(status_code=400, detail="No image provided")

    try:
        # ดึงข้อมูลตำแหน่งจาก get_lat_long_location.php
        location_response = requests.get("http://localhost/api_php/get_lat_long_location.php")
        if location_response.status_code != 200:
            raise HTTPException(status_code=500, detail="Failed to fetch location data")

        location_data = location_response.json()
        logging.info(f"Fetched location data: {location_data}")

        # อ่านและประมวลผลรูปภาพ
        image_bytes = await image.read()
        processed_image = preprocess_image_from_bytes(image_bytes)
        logging.info("Image preprocessing completed.")

        # สกัดฟีเจอร์จาก EfficientNet
        features = efficientnet_model.predict(processed_image)
        features = features.flatten()
        logging.info(f"Extracted features shape: {features.shape}")

        # ใช้ KNN หา 50 เพื่อนบ้านที่ใกล้ที่สุด
        distances, indices = knn_model.kneighbors([features], n_neighbors=50)
        logging.info(f"Distances: {distances}")
        logging.info(f"Indices: {indices}")

        nearest_locations = []
        all_nearby_locations = []  # เก็บสถานที่ทั้งหมดในระยะห่างที่กำหนด
        class_distance_map = {}

        for index in indices[0]:
            location = train_indices_to_class.get(index, "Unknown")
            similarity = round((1 - distances[0][np.where(indices[0] == index)[0][0]]) * 100, 2)

            # ค้นหาข้อมูลพิกัดของสถานที่
            matched_location = next(
                (loc for loc in location_data if loc["Location_Class"] == location), None
            )
            if matched_location:
                loc_lat = float(matched_location["Location_Lat"])
                loc_lon = float(matched_location["Location_Long"])
                distance = haversine(latitude, longitude, loc_lat, loc_lon)
                distance=distance*0.01
                # ตรวจสอบว่าระยะทางอยู่ในขอบเขตที่กำหนด
                if distance <= max_distance:
                    all_nearby_locations.append({
                        "location": location,
                        "distance": round(distance, 2),
                        "latitude": loc_lat,
                        "longitude": loc_lon
                    })

                    # คำนวณคะแนนรวมจากระยะทางและความคล้ายคลึง
                    combined_score = (0.7 * similarity) - (0.3 * distance)
                    class_distance_map[location] = {
                        "similarity": similarity,
                        "combined_score": max(
                            class_distance_map.get(location, {}).get("combined_score", float("-inf")),
                            combined_score,
                        )
                    }

        # เรียงลำดับสถานที่ตามคะแนนรวม
        sorted_locations = sorted(
            class_distance_map.items(),
            key=lambda x: x[1]["combined_score"],
            reverse=True
        )

        for location, scores in sorted_locations:
            if location not in [loc["location"] for loc in nearest_locations]:
                nearest_locations.append({
                    "location": location,
                    "similarity": scores["similarity"],
                    "combined_score": round(scores["combined_score"], 2)
                })
            if len(nearest_locations) == 5:  # จำกัดผลลัพธ์ที่ 5 สถานที่
                break

        logging.info(f"Nearest Locations: {nearest_locations}")
        logging.info(f"All Nearby Locations: {all_nearby_locations}")
        return {
            "nearest_locations": nearest_locations,
            "all_nearby_locations": all_nearby_locations  # สถานที่ทั้งหมดในระยะห่างที่กำหนด
        }

    except Exception as e:
        logging.error(f"Error during prediction: {e}")
        raise HTTPException(status_code=500, detail=str(e))
@app.post("/predict_camera")
async def predict(
    image: UploadFile = File(...),
    latitude: float = Form(...),
    longitude: float = Form(...),
    max_distance: float = Form(...)
):
    logging.info(f"Received latitude: {latitude}, longitude: {longitude}")
    if not image:
        raise HTTPException(status_code=400, detail="No image provided")
    try:
        # ดึงข้อมูลพิกัดจากฐานข้อมูล
        location_response = requests.get("http://localhost/api_php/get_lat_long_location.php")
        if location_response.status_code != 200:
            raise HTTPException(status_code=500, detail="Failed to fetch location data")
        location_data = location_response.json()
        logging.info(f"Fetched location data: {location_data}")
        # กำหนดระยะห่างสูงสุดที่กำหนด
        # ระยะทางหน่วยเป็นกิโลเมตร
        # ตรวจสอบสถานที่ที่อยู่ในระยะที่กำหนด
        all_locations_in_range = []
        for location in location_data:
            loc_lat = float(location["Location_Lat"])
            loc_lon = float(location["Location_Long"])
            distance = haversine(latitude, longitude, loc_lat, loc_lon)
            if distance <= max_distance:
                all_locations_in_range.append({
                    "location": location["Location_Class"],
                    "location_id": location["Location_Id"],
                    "latitude": loc_lat,
                    "longitude": loc_lon,
                    "distance": round(distance, 2),
                })
        logging.info(f"Locations within range: {all_locations_in_range}")
        # อ่านและประมวลผลภาพ
        image_bytes = await image.read()
        processed_image = preprocess_image_from_bytes(image_bytes)
        logging.info("Image preprocessing completed.")
        # สกัดฟีเจอร์จาก EfficientNet
        features = efficientnet_model.predict(processed_image)
        features = features.flatten()
        logging.info(f"Extracted features shape: {features.shape}")
        # ใช้ KNN ในการหาค่า 50 เพื่อนบ้านที่ใกล้ที่สุด
        distances, indices = knn_model.kneighbors([features], n_neighbors=50)
        logging.info(f"Distances: {distances}")
        logging.info(f"Indices: {indices}")
        # สร้างผลลัพธ์สถานที่ที่ใกล้เคียงที่สุด
        nearest_locations = []
        class_similarity_map = {}
        for index in indices[0]:
            location = train_indices_to_class.get(index, "Unknown")
            similarity = round((1 - distances[0][np.where(indices[0] == index)[0][0]]) * 100, 2)
            if location not in class_similarity_map:
                class_similarity_map[location] = similarity
            else:
                class_similarity_map[location] = max(class_similarity_map[location], similarity)
        sorted_locations = sorted(class_similarity_map.items(), key=lambda x: x[1], reverse=True)
        for location, similarity in sorted_locations:
            if location not in [loc["location"] for loc in nearest_locations]:
                nearest_locations.append({"location": location, "similarity": similarity})
            if len(nearest_locations) == 5:
                break
        logging.info(f"Predicted Nearest Locations: {nearest_locations}")
        # เปรียบเทียบสถานที่ในระยะกับผลลัพธ์การทำนาย
        matched_locations = []
        for pred_loc in nearest_locations:
            for range_loc in all_locations_in_range:
                if pred_loc["location"] == range_loc["location"]:
                    matched_locations.append({
                        "location": pred_loc["location"],
                        "similarity": pred_loc["similarity"],
                        "distance": range_loc["distance"],
                        "location_id": range_loc["location_id"],
                    })
        logging.info(f"Matched Locations: {matched_locations}")
        logging.info(f"Locations in Range: {all_locations_in_range}")
        return {
            "matched_locations": matched_locations,
            "all_locations_in_range": all_locations_in_range,
            "nearest_locations": nearest_locations,
        }
    except Exception as e:
        logging.error(f"Error during prediction: {e}")
        raise HTTPException(status_code=500, detail=str(e))
SIMILARITY_THRESHOLD = 50  # กำหนดค่าความคล้ายขั้นต่ำเป็น 50%



from tensorflow.keras.preprocessing import image
from sklearn.neighbors import KNeighborsClassifier
import numpy as np
import joblib
import tensorflow as tf
from tensorflow.keras.applications.efficientnet import EfficientNetB7, preprocess_input
from tensorflow.keras.preprocessing.image import load_img, img_to_array
from tensorflow.keras.models import Model
from sklearn.neighbors import KNeighborsClassifier
from sklearn.model_selection import train_test_split
from sklearn.metrics import accuracy_score, classification_report, confusion_matrix
import shutil
from typing import List
# กำหนด path ของไฟล์โมเดล
FEATURES_PATH = "models/features.npy"
LABELS_PATH = "models/labels.npy"
KNN_MODEL_PATH = "models/knn_model.joblib"
NEW_IMAGE_PATH = "C:/xampp/htdocs/api_php/new_image_unverified"


dataset_path = "C:/xampp/htdocs/api_php/new_image_unverified"
save_dir = "models/"
os.makedirs(save_dir, exist_ok=True)
img_height, img_width = 600, 600
k = 5

# Load EfficientNetB7 Model
base_model = EfficientNetB7(weights='imagenet', include_top=False, input_shape=(img_height, img_width, 3))
x = base_model.output
x = tf.keras.layers.GlobalAveragePooling2D()(x)
feature_extractor = Model(inputs=base_model.input, outputs=x)


def extract_feature(image_path):
    img = load_img(image_path, target_size=(img_height, img_width))
    img_array = img_to_array(img)
    img_array = np.expand_dims(img_array, axis=0)
    img_array = preprocess_input(img_array)
    return feature_extractor.predict(img_array)[0]


@app.post("/extract_features/")
async def extract_features():
    features = []
    labels = []
    index_to_class = {}

    for class_name in os.listdir(dataset_path):
        class_dir = os.path.join(dataset_path, class_name)
        if os.path.isdir(class_dir):
            for image_name in os.listdir(class_dir):
                image_path = os.path.join(class_dir, image_name)
                feature = extract_feature(image_path)
                features.append(feature)
                labels.append(class_name)
    
    features = np.array(features)
    np.save(os.path.join(save_dir, 'features.npy'), features)
    np.save(os.path.join(save_dir, 'labels.npy'), np.array(labels))
    return {"message": "Features extracted successfully."}


@app.post("/extract_features_bulk_old/")
async def extract_features_bulk():
    existing_features = np.load(os.path.join(save_dir, 'features.npy'))
    existing_labels = np.load(os.path.join(save_dir, 'labels.npy'))
    
    # โหลด train_indices_to_class ถ้ามีอยู่แล้ว
    train_indices_to_class_path = os.path.join(save_dir, 'train_indices_to_class.joblib')
    if os.path.exists(train_indices_to_class_path):
        train_indices_to_class = joblib.load(train_indices_to_class_path)
    else:
        train_indices_to_class = {}

    new_features = []
    new_labels = []

    for class_name in os.listdir(dataset_path):
        class_dir = os.path.join(dataset_path, class_name)
        if os.path.isdir(class_dir):
            # ตรวจสอบและอัปเดต index_to_class
            if class_name not in index_to_class.values():
                new_class_id = len(index_to_class)  
                index_to_class[new_class_id] = class_name  

            class_id = list(index_to_class.keys())[list(index_to_class.values()).index(class_name)]

            for image_name in os.listdir(class_dir):
                image_path = os.path.join(class_dir, image_name)
                feature = extract_feature(image_path)
                new_features.append(feature)
                new_labels.append(class_id)

                # อัปเดต train_indices_to_class
                new_index = len(existing_features) + len(new_features) - 1
                train_indices_to_class[new_index] = class_name

    if new_features:
        updated_features = np.vstack((existing_features, np.array(new_features)))
        updated_labels = np.concatenate((existing_labels, np.array(new_labels)))
    else:
        updated_features, updated_labels = existing_features, existing_labels

    # บันทึกฟีเจอร์, labels และ index mapping
    np.save(os.path.join(save_dir, 'features.npy'), updated_features)
    np.save(os.path.join(save_dir, 'labels.npy'), updated_labels)
    joblib.dump(index_to_class, os.path.join(save_dir, 'index_to_class.joblib'))
    joblib.dump(train_indices_to_class, TRAIN_INDICES_TO_CLASS)
    

    return {
        "message": "Bulk feature extraction completed.",
        "new_classes": list(index_to_class.values()),
        "train_indices_count": len(train_indices_to_class)
    }
@app.post("/retrain_old/")
async def retrain():
    features = np.load(os.path.join(save_dir, 'features.npy'))
    labels = np.load(os.path.join(save_dir, 'labels.npy'))

    if len(set(labels)) < 2:
        return {"error": "มีคลาสไม่เพียงพอสำหรับการเทรน"}

    # แบ่งข้อมูล
    X_train, X_test, y_train, y_test = train_test_split(
        features, labels, test_size=0.2, random_state=42, stratify=labels
    )

    # ✅ อัปเดต train_indices_to_class ตาม Colab
    train_indices_to_class = {i: index_to_class[y] for i, y in enumerate(y_train.tolist())}

    # สร้างโมเดล KNN
    knn = KNeighborsClassifier(n_neighbors=k, metric='cosine')
    knn.fit(X_train, y_train)

    # ประเมินผล
    y_pred = knn.predict(X_test)
    accuracy = accuracy_score(y_test, y_pred)

    # บันทึกโมเดล + index mapping
    joblib.dump(knn, os.path.join(save_dir, 'knn_model.joblib'))
    joblib.dump(train_indices_to_class, os.path.join(save_dir, 'train_indices_to_class.joblib'))
    print('accuracy',accuracy*100)
    return {
        "message": "Model retrained successfully.",
        "accuracy": f"{accuracy * 100:.2f}%",
        "train_indices_count": len(train_indices_to_class)
    }

@app.post("/compare_accuracy/")
async def compare_accuracy():
    try:
        knn_model_path = os.path.join(save_dir, 'knn_model.joblib')

        if not os.path.exists(knn_model_path):
            return {"error": "dont have file model"}

        # 📌 โหลดโมเดลเก่า
        knn_old = joblib.load(knn_model_path)

        # 📌 โหลดชุดข้อมูลเก่า
        X_train_old = np.load(os.path.join(save_dir, 'X_train.npy'))
        X_test_old = np.load(os.path.join(save_dir, 'X_test.npy'))
        y_train_old = np.load(os.path.join(save_dir, 'y_train.npy'))
        y_test_old = np.load(os.path.join(save_dir, 'y_test.npy'))

        # 📌 โหลดฟีเจอร์ใหม่ทั้งหมด
        features = np.load(os.path.join(save_dir, 'features.npy'))
        labels = np.load(os.path.join(save_dir, 'labels.npy'))

        # 📌 แบ่งข้อมูลใหม่
        X_train_new, X_test_new, y_train_new, y_test_new = train_test_split(
            features, labels, test_size=0.2, random_state=42, stratify=labels
        )

        # **ตรวจสอบว่าข้อมูลใหม่โหลดสำเร็จหรือไม่**
        if len(X_test_new) == 0 or len(y_test_new) == 0:
            return {"error": "dont have dataset"}

        # 📌 รวมข้อมูลเก่า + ใหม่
        X_train_all = np.concatenate((X_train_old, X_train_new))
        X_test_all = np.concatenate((X_test_old, X_test_new))
        y_train_all = np.concatenate((y_train_old, y_train_new))
        y_test_all = np.concatenate((y_test_old, y_test_new))

        # 📌 ทดสอบโมเดลเก่า
        old_accuracy_old_test = accuracy_score(y_test_old, knn_old.predict(X_test_old)) * 100
        old_accuracy_new_test = accuracy_score(y_test_new, knn_old.predict(X_test_new)) * 100

        # 📌 เทรนโมเดลใหม่
        knn_new = KNeighborsClassifier(n_neighbors=5, metric='cosine')
        knn_new.fit(X_train_all, y_train_all)

        # 📌 ทดสอบโมเดลใหม่
        new_accuracy_old_test = accuracy_score(y_test_old, knn_new.predict(X_test_old)) * 100
        new_accuracy_new_test = accuracy_score(y_test_new, knn_new.predict(X_test_new)) * 100

        # 📌 บันทึกโมเดลใหม่
        joblib.dump(knn_new, knn_model_path)

        # 📌 Debugging
        result = {
            "message": "compare_accuracy successfully.",
            "old_model_accuracy_on_old_test": f"{old_accuracy_old_test:.2f}%",
            "old_model_accuracy_on_new_test": f"{old_accuracy_new_test:.2f}%",
            "new_model_accuracy_on_old_test": f"{new_accuracy_old_test:.2f}%",
            "new_model_accuracy_on_new_test": f"{new_accuracy_new_test:.2f}%",
        }

        print("API Response:", result)  # ✅ Debugging
        return result
    
    except Exception as e:
        print(f"API Error: {str(e)}")  # 🔴 ตรวจสอบ error
        return {"error": f"API Error: {str(e)}"}
@app.post("/extract_features_bulk1/")
async def extract_features_bulk():
    existing_features = np.load(os.path.join(save_dir, 'features.npy'))
    existing_labels = np.load(os.path.join(save_dir, 'labels.npy'))

    train_indices_to_class_path = os.path.join(save_dir, 'train_indices_to_class.joblib')
    if os.path.exists(train_indices_to_class_path):
        train_indices_to_class = joblib.load(train_indices_to_class_path)
    else:
        train_indices_to_class = {}

    new_features, new_labels = [], []

    for class_name in os.listdir(dataset_path):
        class_dir = os.path.join(dataset_path, class_name)
        if os.path.isdir(class_dir):
            if class_name not in index_to_class.values():
                new_class_id = len(index_to_class)  
                index_to_class[new_class_id] = class_name  

            class_id = list(index_to_class.keys())[list(index_to_class.values()).index(class_name)]

            for image_name in os.listdir(class_dir):
                image_path = os.path.join(class_dir, image_name)
                feature = extract_feature(image_path)
                new_features.append(feature)
                new_labels.append(class_id)

                new_index = len(existing_features) + len(new_features) - 1
                train_indices_to_class[new_index] = class_name

    if new_features:
        updated_features = np.vstack((existing_features, np.array(new_features)))
        updated_labels = np.concatenate((existing_labels, np.array(new_labels)))
    else:
        updated_features, updated_labels = existing_features, existing_labels

    # **เพิ่มการแบ่งข้อมูล Train/Test**
    X_train, X_test, y_train, y_test = train_test_split(
        updated_features, updated_labels, test_size=0.2, random_state=42, stratify=updated_labels
    )

    # ✅ บันทึกข้อมูลที่แบ่งแล้ว
    np.save(os.path.join(save_dir, 'X_train.npy'), X_train)
    np.save(os.path.join(save_dir, 'X_test.npy'), X_test)
    np.save(os.path.join(save_dir, 'y_train.npy'), y_train)
    np.save(os.path.join(save_dir, 'y_test.npy'), y_test)

    # ✅ บันทึก index mapping
    np.save(os.path.join(save_dir, 'features.npy'), updated_features)
    np.save(os.path.join(save_dir, 'labels.npy'), updated_labels)
    joblib.dump(index_to_class, os.path.join(save_dir, 'index_to_class.joblib'))
    joblib.dump(train_indices_to_class, train_indices_to_class_path)

    return {
        "message": "Bulk feature extraction completed.",
        "new_classes": list(index_to_class.values()),
        "train_indices_count": len(train_indices_to_class)
    }
@app.post("/retrain1/")
async def retrain():
    features = np.load(os.path.join(save_dir, 'features.npy'))
    labels = np.load(os.path.join(save_dir, 'labels.npy'))
    
    if len(set(labels)) < 2:
        return {"error": "มีคลาสไม่เพียงพอสำหรับการเทรน"}
    
    # โหลดชุดข้อมูลเทสเดิม
    X_test_path = os.path.join(save_dir, 'X_test.npy')
    y_test_path = os.path.join(save_dir, 'y_test.npy')
    
    if os.path.exists(X_test_path) and os.path.exists(y_test_path):
        X_test_old = np.load(X_test_path)
        y_test_old = np.load(y_test_path)
    else:
        X_test_old, y_test_old = None, None
    
    # โหลดโมเดลเก่าถ้ามีอยู่
    knn_old_path = os.path.join(save_dir, 'knn_model.joblib')
    if os.path.exists(knn_old_path) and X_test_old is not None:
        knn_old = joblib.load(knn_old_path)
        y_pred_old = knn_old.predict(X_test_old)
        old_accuracy = accuracy_score(y_test_old, y_pred_old)
    else:
        old_accuracy = None
    
    # แบ่งข้อมูลใหม่
    X_train, X_test_new, y_train, y_test_new = train_test_split(
        features, labels, test_size=0.2, random_state=42, stratify=labels
    )
    
    # เทรนโมเดลใหม่
    knn_new = KNeighborsClassifier(n_neighbors=k, metric='cosine')
    knn_new.fit(X_train, y_train)
    y_pred_new = knn_new.predict(X_test_new)
    new_accuracy = accuracy_score(y_test_new, y_pred_new)
    
    # เปรียบเทียบผลลัพธ์
    if old_accuracy is None or new_accuracy >= old_accuracy - 0.02:  # อนุญาตให้ลดลงเล็กน้อย (2%)
        joblib.dump(knn_new, knn_old_path)
        joblib.dump({i: index_to_class[y] for i, y in enumerate(y_train.tolist())}, 
                    os.path.join(save_dir, 'train_indices_to_class.joblib'))
        
        # รวมข้อมูลเทสใหม่เข้ากับเดิม
        if X_test_old is not None:
            X_test_final = np.vstack((X_test_old, X_test_new))
            y_test_final = np.concatenate((y_test_old, y_test_new))
        else:
            X_test_final, y_test_final = X_test_new, y_test_new
        
        np.save(X_test_path, X_test_final)
        np.save(y_test_path, y_test_final)
        print("Model retrained successfully.")  # ✅ Debugging
        print(f"Old Accuracy: {old_accuracy * 100:.2f}%")
        print(f"New Accuracy: {new_accuracy * 100:.2f}%")
        print("Updated model saved.")  # ✅ Debugging
        return {
            "message": "Model retrained successfully.",
            "old_accuracy": f"{old_accuracy * 100:.2f}%" if old_accuracy else "N/A",
            "new_accuracy": f"{new_accuracy * 100:.2f}%",
            "status": "Updated model saved."
        }
    else:
        print("Model retraining skipped.")  # ✅ Debugging
        print(f"Old Accuracy: {old_accuracy * 100:.2f}%")  # ✅ Debugging
        print(f"New Accuracy: {new_accuracy * 100:.2f}%")  # ✅ Debugging
        print("Old Model retained.")  # ✅ Debugging
        return {
            "message": "Model retraining skipped.",
            "old_accuracy": f"{old_accuracy * 100:.2f}%",
            "new_accuracy": f"{new_accuracy * 100:.2f}%",
            "status": "Old model retained."
        }


@app.post("/extract_features_bulk/")
async def extract_features_bulk():
    existing_features = np.load(os.path.join(save_dir, 'features.npy'))
    existing_labels = np.load(os.path.join(save_dir, 'labels.npy'))

    train_indices_to_class_path = os.path.join(save_dir, 'train_indices_to_class.joblib')
    if os.path.exists(train_indices_to_class_path):
        train_indices_to_class = joblib.load(train_indices_to_class_path)
    else:
        train_indices_to_class = {}

    new_features, new_labels = [], []

    for class_name in os.listdir(dataset_path):
        class_dir = os.path.join(dataset_path, class_name)
        if os.path.isdir(class_dir):
            # ใช้ index_to_class ที่อัปเดตแล้ว
            if class_name not in index_to_class.values():
                new_class_id = len(index_to_class)  
                index_to_class[new_class_id] = class_name  

            class_id = list(index_to_class.keys())[list(index_to_class.values()).index(class_name)]

            for image_name in os.listdir(class_dir):
                image_path = os.path.join(class_dir, image_name)
                feature = extract_feature(image_path)
                new_features.append(feature)
                new_labels.append(class_id)

                new_index = len(existing_features) + len(new_features) - 1
                train_indices_to_class[new_index] = class_name

    if new_features:
        updated_features = np.vstack((existing_features, np.array(new_features)))
        updated_labels = np.concatenate((existing_labels, np.array(new_labels)))
    else:
        updated_features, updated_labels = existing_features, existing_labels

    # ✅ แบ่งข้อมูล Train/Test
    X_train, X_test, y_train, y_test = train_test_split(
        updated_features, updated_labels, test_size=0.2, random_state=42, stratify=updated_labels
    )

    # ✅ บันทึกข้อมูล
    np.save(os.path.join(save_dir, 'X_train.npy'), X_train)
    np.save(os.path.join(save_dir, 'X_test.npy'), X_test)
    np.save(os.path.join(save_dir, 'y_train.npy'), y_train)
    np.save(os.path.join(save_dir, 'y_test.npy'), y_test)

    np.save(os.path.join(save_dir, 'features.npy'), updated_features)
    np.save(os.path.join(save_dir, 'labels.npy'), updated_labels)
    joblib.dump(index_to_class, INDEX_TO_CLASS_PATH)
    joblib.dump(train_indices_to_class, train_indices_to_class_path)

    return {
        "message": "Bulk feature extraction completed.",
        "new_classes": list(index_to_class.values()),
        "train_indices_count": len(train_indices_to_class)
    }

@app.post("/retrain/")
async def retrain():
    features = np.load(os.path.join(save_dir, 'features.npy'))
    labels = np.load(os.path.join(save_dir, 'labels.npy'))
    
    if len(set(labels)) < 2:
        return {"error": "มีคลาสไม่เพียงพอสำหรับการเทรน"}
    
    # ✅ โหลดชุดข้อมูลเทสเดิม
    X_test_path = os.path.join(save_dir, 'X_test.npy')
    y_test_path = os.path.join(save_dir, 'y_test.npy')
    
    if os.path.exists(X_test_path) and os.path.exists(y_test_path):
        X_test_old = np.load(X_test_path)
        y_test_old = np.load(y_test_path)
    else:
        X_test_old, y_test_old = None, None
    
    # ✅ โหลดโมเดลเก่าถ้ามีอยู่
    knn_old_path = os.path.join(save_dir, 'knn_model.joblib')
    if os.path.exists(knn_old_path) and X_test_old is not None:
        knn_old = joblib.load(knn_old_path)
        y_pred_old = knn_old.predict(X_test_old)
        old_accuracy = accuracy_score(y_test_old, y_pred_old)
    else:
        old_accuracy = None
    
    # ✅ แบ่งข้อมูลใหม่
    X_train, X_test_new, y_train, y_test_new = train_test_split(
        features, labels, test_size=0.2, random_state=42, stratify=labels
    )
    
    # ✅ เทรนโมเดลใหม่
    knn_new = KNeighborsClassifier(n_neighbors=k, metric='cosine')
    knn_new.fit(X_train, y_train)
    y_pred_new = knn_new.predict(X_test_new)
    new_accuracy = accuracy_score(y_test_new, y_pred_new)
    
    # ✅ บันทึกค่าความแม่นยำย้อนหลัง
    accuracy_log_path = os.path.join(save_dir, 'accuracy_history.txt')
    with open(accuracy_log_path, 'a') as f:
        f.write(f"Old: {old_accuracy * 100 if old_accuracy else 'N/A'}% | New: {new_accuracy * 100:.2f}%\n")

    # ✅ เปรียบเทียบผลลัพธ์
    if old_accuracy is None or new_accuracy >= old_accuracy - 0.02:  
        joblib.dump(knn_new, knn_old_path)
        joblib.dump({i: index_to_class[y] for i, y in enumerate(y_train.tolist())}, 
                    os.path.join(save_dir, 'train_indices_to_class.joblib'))
        
        if X_test_old is not None:
            X_test_final = np.vstack((X_test_old, X_test_new))
            y_test_final = np.concatenate((y_test_old, y_test_new))
        else:
            X_test_final, y_test_final = X_test_new, y_test_new
        
        np.save(X_test_path, X_test_final)
        np.save(y_test_path, y_test_final)
        print("Model retrained successfully.")  # ✅ Debugging
        print(f"Old Accuracy: {old_accuracy * 100:.2f}%")
        print(f"New Accuracy: {new_accuracy * 100:.2f}%")
        print("Updated model saved.")
        return {
            "message": "Model retrained successfully.",
            "old_accuracy": f"{old_accuracy * 100:.2f}%" if old_accuracy else "N/A",
            "new_accuracy": f"{new_accuracy * 100:.2f}%",
            "status": "Updated model saved."
        }
    else:
        print("Model retraining skipped.")  # ✅ Debugging
        print(f"Old Accuracy: {old_accuracy * 100:.2f}%")  # ✅ Debugging   
        print(f"New Accuracy: {new_accuracy * 100:.2f}%")
        print("Old Model retained.")
        return {
            "message": "Model retraining skipped.",
            "old_accuracy": f"{old_accuracy * 100:.2f}%",
            "new_accuracy": f"{new_accuracy * 100:.2f}%",
            "status": "Old model retained."
        }