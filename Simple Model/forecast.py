import pandas as pd
from prophet import Prophet
import json
from datetime import datetime

# Load input data
with open('prophet_input.json') as f:
    data = json.load(f)

df = pd.DataFrame(data)

# Fit model
model = Prophet()
model.fit(df)

# Predict next 6 months
future = model.make_future_dataframe(periods=6, freq='M')
forecast = model.predict(future)

# Extract relevant forecast and convert dates to strings
result = forecast[['ds', 'yhat']].tail(6)
result['ds'] = result['ds'].dt.strftime('%Y-%m-%d')
result = result.to_dict(orient='records')

# Save output
with open('forecast_output.json', 'w') as f:
    json.dump(result, f)
