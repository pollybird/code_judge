# code_judge
该程序需调用jobe评测服务器，以下为jobe评测服务器安装的方式：

Jobe 是一个基于 RESTful API 的编程代码评测服务器，可用于自动评测多种编程语言的代码。以下是部署 Jobe 评测服务器的详细步骤：
1. 环境准备
操作系统：建议使用 Linux 系统，如 Ubuntu 18.04 及以上版本。
软件依赖：需要安装 Docker 和 Docker Compose，因为 Jobe 官方推荐使用 Docker 进行部署。
安装 Docker
# 更新系统软件包列表
sudo apt update
# 安装必要的依赖包
sudo apt install apt-transport-https ca-certificates curl software-properties-common
# 添加 Docker 的官方 GPG 密钥
curl -fsSL https://download.docker.com/linux/ubuntu/gpg | sudo apt-key add -
# 添加 Docker 的 APT 仓库
sudo add-apt-repository "deb [arch=amd64] https://download.docker.com/linux/ubuntu $(lsb_release -cs) stable"
# 更新软件包列表
sudo apt update
# 安装 Docker CE
sudo apt install docker-ce
# 验证 Docker 是否安装成功
sudo docker run hello-world
安装 Docker Compose
# 下载 Docker Compose 二进制文件
sudo curl -L "https://github.com/docker/compose/releases/download/1.29.2/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
# 添加可执行权限
sudo chmod +x /usr/local/bin/docker-compose
# 验证 Docker Compose 是否安装成功
docker-compose --version
2. 获取 Jobe 代码
从 Jobe 的 GitHub 仓库克隆代码到本地：
git clone https://github.com/trampgeek/jobe.git
cd jobe
3. 配置 Jobe
在 jobe 目录下，有一个 docker-compose.yml 文件，用于配置 Jobe 服务。你可以根据需要进行一些调整，例如修改端口映射等。默认情况下，Jobe 会监听 4736 端口。
4. 构建和启动 Jobe 容器
使用 Docker Compose 构建并启动 Jobe 容器：
docker-compose up -d
-d 参数表示在后台运行容器。
5. 验证 Jobe 服务是否正常运行
可以使用 curl 命令向 Jobe 发送一个简单的请求来验证服务是否正常：
curl http://localhost:4736/jobe/index.php/restapi/languages
如果服务正常运行，你将看到 Jobe 支持的编程语言列表。
6. 配置防火墙（可选）
如果你的服务器启用了防火墙，需要开放 Jobe 监听的端口（默认是 4736）：
sudo ufw allow 4736
