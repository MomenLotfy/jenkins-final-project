🧪 Lab 2 — Containerization, Security & CI/CD Pipeline

> Topics Covered: Docker • SonarQube • Jenkins • GitHub Private Repo • Trivy • CI/CD Automation




---

🎯 Lab Goal

This lab focuses on building a complete CI/CD pipeline using Jenkins that automates:

Source code retrieval from GitHub

Static code analysis using SonarQube

Docker image creation

Security scanning of images

Publishing images to Docker Hub

Deployment to a local server or AWS EC2



---

📌 Prerequisites

Before starting, ensure you have:

A working GitHub repository from Lab 1

Jenkins installed and running

Docker installed on the Jenkins machine

Docker Hub account

GitHub Personal Access Token (PAT)

Basic Jenkins setup completed



---

🧭 Pipeline Flow

The CI/CD process follows this sequence:

1. Checkout source code from GitHub


2. Perform static analysis using SonarQube


3. Build Docker image


4. Scan image vulnerabilities using Trivy


5. Push image to Docker Hub


6. Deploy application


7. Clean up unused Docker resources




---

🔐 Task 1 — GitHub Private Repository Access

To allow Jenkins to access a private repository, authentication via PAT is required.

Jenkins Credentials Setup

Kind: Username with password

Username: GitHub username

Password: Personal Access Token

ID: github-pat-creds


Jenkinsfile Checkout Stage

checkout scmGit(
    branches: [[name: 'main']],
    userRemoteConfigs: [[
        url: 'https://github.com/<your-username>/<your-repo>.git',
        credentialsId: 'github-pat-creds'
    ]]
)


---

🔍 Task 2 — SonarQube Static Code Analysis

Start SonarQube

docker run -d \
  --name sonarqube \
  -p 9000:9000 \
  sonarqube:lts-community

Access it via:

http://<VM-IP>:9000

Default login: admin / admin


---

Project Setup

Project Name: service-app

Project Key: service-app

Generate authentication token for Jenkins integration



---

Jenkins Configuration

Install: SonarQube Scanner plugin

Tool Name: SonarScanner

Server Name: SonarQube-Server

Credentials ID: sonar-token



---

Jenkins Pipeline Stage

stage('SonarQube Analysis') {
    steps {
        withSonarQubeEnv('SonarQube-Server') {
            sh """
                ${tool 'SonarScanner'}/bin/sonar-scanner \
                -Dsonar.projectKey=service-app \
                -Dsonar.sources=./src \
                -Dsonar.host.url=http://<VM-IP>:9000
            """
        }
    }
}


---

🐳 Task 3 — Docker Containerization

Dockerfile

FROM php:8.2-cli

WORKDIR /app

COPY . .

CMD ["php", "./src/index.php"]


---

Local Build Test

docker build -t service-app:test .
docker run --rm service-app:test


---

Jenkins Build Stage

stage('Build Docker Image') {
    steps {
        sh 'docker build -t <dockerhub-user>/service-app:${BUILD_NUMBER} .'
    }
}


---

🔐 Task 4 — Docker Hub Authentication

Create a Docker Hub access token and store it in Jenkins.

Credentials

ID: dockerhub-creds

Type: Username + Password (Token used as password)



---

🛡 Task 5 — Image Security Scanning (Trivy)

Install Trivy

sudo apt-get update
sudo apt-get install -y wget apt-transport-https gnupg lsb-release

Verify Installation

trivy --version


---

Jenkins Scan Stage

stage('Trivy Scan') {
    steps {
        sh """
            trivy image \
            --exit-code 1 \
            --severity CRITICAL \
            ${IMAGE_TAG}
        """
    }
}


---

🚀 Task 6 — Build, Push & Deploy

After image creation and validation:

Push image to Docker Hub

Deploy to local VM or AWS EC2



---

Local Deployment Example

stage('Deploy') {
    steps {
        sh """
            docker stop app || true
            docker rm app || true
            docker run -d -p 8081:8081 --name app ${IMAGE_TAG}
        """
    }
}


---

🧹 Task 7 — Cleanup Process

Ensure system resources are cleaned after each build.

post {
    always {
        sh """
            docker rmi ${IMAGE_TAG} || true
            docker image prune -f
        """
    }
}


---

🏁 Final Pipeline Order

1. Checkout code


2. SonarQube Analysis


3. Build Docker Image


4. Trivy Scan


5. Push to Docker Hub


6. Deploy Application


7. Cleanup




---

🔑 Credentials Summary

ID	Purpose

github-pat-creds	GitHub repository access
sonar-token	SonarQube authentication
dockerhub-creds	Docker Hub push access
ec2-ssh-key	Remote deployment (optional)
