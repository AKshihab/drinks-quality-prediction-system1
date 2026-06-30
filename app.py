from flask import Flask, render_template, request

app = Flask(__name__)


@app.route("/")
def login():
    return render_template("login.html")


@app.route("/register")
def register():
    return render_template("register.html")


@app.route("/dashboard")
def dashboard():
    return render_template("index.html", user_name="Group 7 User")


@app.route("/predict", methods=["POST"])
def predict():
    # Dummy result for UI testing only
    prediction = "5.8"
    return render_template("results.html", prediction=prediction)


if __name__ == "__main__":
    app.run(debug=True)
