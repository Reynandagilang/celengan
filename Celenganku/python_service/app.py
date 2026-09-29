from flask import Flask, request, jsonify
from datetime import datetime, timedelta
from flask_cors import CORS

app = Flask(__name__)
CORS(app)

@app.route('/predict', methods=['POST'])
def predict():
    data = request.json
    transactions = data.get('transactions', [])
    target_amount = data.get('target_amount', 0)
    current_amount = data.get('current_amount', 0)
    
    if not transactions or len(transactions) < 2 or target_amount <= current_amount:
        return jsonify({"predicted_date": None, "message": "Not enough data or already completed"})

    total_saved = current_amount
    
    first_date = datetime.strptime(transactions[-1]['created_at'], '%Y-%m-%d %H:%M:%S')
    current_date = datetime.now()
    
    # Menghitung durasi murni dari hari pertama menabung sampai hari ini (memperhitungkan hari bolos menabung)
    days_diff = (current_date - first_date).days
    if days_diff <= 0:
        days_diff = 1 # Minimal 1 hari untuk menghindari error pembagian
        
    avg_per_day = total_saved / days_diff
    remaining_amount = target_amount - current_amount
    
    if avg_per_day <= 0:
        return jsonify({"predicted_date": None})
        
    days_needed = remaining_amount / avg_per_day
    predicted_date = current_date + timedelta(days=days_needed)
    
    return jsonify({
        "predicted_date": predicted_date.strftime('%Y-%m-%d'),
        "days_needed": round(days_needed),
        "avg_per_day": round(avg_per_day, 2)
    })

if __name__ == '__main__':
    app.run(port=5000, debug=True)
