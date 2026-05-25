pipeline {
    // توجيه كافة مراحل الـ Pipeline لتنفذ داخل سيرفر AWS EC2 المربوط كـ Agent
    agent { label 'ec2-worker' }

    environment {
        DOCKERHUB_USER = 'your-dockerhub-username' // ضع اسم مستخدم Docker Hub هنا
        IMAGE_NAME     = 'service-app'
        IMAGE_TAG      = "${DOCKERHUB_USER}/${IMAGE_NAME}:${BUILD_NUMBER}"
        SERVER_IP      = 'YOUR_EC2_PUBLIC_IP'      // ضع الـ Public IP الخاص بالـ EC2 هنا
    }

    stages {
        // TASK 1: Secure Access to Private Repository via PAT
        stage('Checkout') {
            steps {
                checkout scmGit(
                    branches: [[name: 'main']],
                    userRemoteConfigs: [[
                        url: "https://github.com/${DOCKERHUB_USER}/${IMAGE_NAME}.git",
                        credentialsId: 'github-pat-creds'
                    ]]
                )
            }
        }

        // TASK 2: SonarQube Static Code Analysis
        stage('SonarQube Analysis') {
            steps {
                withSonarQubeEnv('SonarQube-Server') {
                    sh """
                        ${tool 'SonarScanner'}/bin/sonar-scanner \
                        -Dsonar.projectKey=service-app \
                        -Dsonar.sources=./src \
                        -Dsonar.host.url=http://${SERVER_IP}:9000
                    """
                }
            }
        }

        // TASK 3: Build Docker Image
        stage('Build Docker Image') {
            steps {
                sh "docker build -t ${IMAGE_TAG} ."
            }
        }

        // TASK 6: Trivy Vulnerability Scanning (موقعه الإلزامي بعد الـ Build وقبل الـ Push)
        stage('Trivy Image Scan') {
            steps {
                sh """
                    trivy image \
                    --exit-code 1 \
                    --severity CRITICAL \
                    --no-progress \
                    ${IMAGE_TAG}
                """
            }
        }

        // TASK 4 & 5: Secure Registry Access & Push Stage
        stage('Push to Docker Hub') {
            steps {
                withCredentials([usernamePassword(
                    credentialsId: 'dockerhub-creds',
                    usernameVariable: 'DOCKER_USER',
                    passwordVariable: 'DOCKER_PASS'
                )]) {
                    sh "echo '${DOCKER_PASS}' | docker login -u '${DOCKER_USER}' --password-stdin"
                    sh "docker push ${IMAGE_TAG}"
                }
            }
        }

        // TASK 8: Deployment (Option B - Deployment on the AWS EC2 Agent)
        stage('Deploy') {
            steps {
                // إيقاف الحاوية القديمة إن وجدت لتجنب تضارب المنفذ 8081
                sh "docker stop ${IMAGE_NAME} || true"
                sh "docker rm ${IMAGE_NAME} || true"
                
                // تشغيل الحاوية الجديدة على الـ EC2 وجعلها متاحة خارجياً
                sh "docker run -d --name ${IMAGE_NAME} -p 8081:8081 ${IMAGE_TAG}"
            }
        }
    }

    // TASK 7: Resource Cleanup (ينفذ دائماً على مستوى الـ Pipeline لحماية مساحة الـ Agent)
    post {
        always {
            sh """
                docker rmi ${IMAGE_TAG} || true
                docker image prune -f
            """
        }
    }
}
