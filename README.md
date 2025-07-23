# 用户操作平台项目介绍

## 项目简介

本项目旨在开发一个功能完备的用户操作平台，目前已演进为集**账户管理、社交互动、即时通讯**于一体的综合性系统。通过持续迭代，平台已从基础账户系统发展为支持多层级社交互动的成熟解决方案，为用户提供安全、高效的操作体验。

## 核心功能体系

### 1. 账户管理
- **注册与登录**
  - 用户名/密码注册，自动分配唯一ID
  - 多设备登录状态同步
- **个人信息管理**
  - 昵称/头像/性别/生日/简介设置
  - 密码修改与安全验证
- **账户控制**
  - 账户注销与数据清除
  - 登录状态实时监控

### 2. 社交关系管理
- **好友系统**
  - 双向好友添加/删除机制
  - 好友请求发送/接收/处理
  - 完整好友列表展示
- **群组系统**
  - 群组创建/解散/权限管理
  - 群成员邀请/移除
  - 群资料修改（名称/头像）

### 3. 即时通讯
- **好友聊天**
  - 一对一实时消息传输
  - 文字/文件/富媒体支持
- **群组聊天**
  - 多人实时群聊室
  - 聊天记录云端同步
- **会话管理**
  - 聊天会话分类（好友/群组）
  - 聊天记录导出(TXT)

### 4. 通知中心
- **社交通知**
  - 好友请求实时提醒
  - 群组邀请通知
  - 消息到达提示
- **系统通知**
  - 好友关系变更通知
  - 好友注销通知
  - 群解散通知

## 配置要求

### 数据库配置

本项目使用MySQL作为数据库。在配置文件中，请确保数据库连接信息正确，请在[配置文件](conf/settings.ini)中相应修改数据库连接信息。

#### 数据表结构

为了确保数据的正确存储和访问，您需要创建以下数据表结构：

```sql
CREATE TABLE users (
  id int PRIMARY KEY NOT NULL,
  user varchar(32) NOT NULL,
  password varchar(60) NOT NULL,
  created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  nick varchar(100) NOT NULL,
  user_avatar varchar(72) NOT NULL DEFAULT '/default.png',
  gender enum('M','W','N') NOT NULL DEFAULT 'N',
  birth date DEFAULT NULL,
  bio varchar(1024) NOT NULL DEFAULT '',
  unbanned_at timestamp NULL DEFAULT NULL,
  is_admin tinyint(1) NOT NULL DEFAULT '0',
  background varchar(69) DEFAULT NULL,
  theme_color varbinary(3) DEFAULT NULL
);
CREATE TABLE user_session (
  id int PRIMARY KEY NOT NULL AUTO_INCREMENT,
  user int DEFAULT NULL,
  token varchar(32) NOT NULL,
  expires timestamp NOT NULL,
  creation timestamp NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE user_remarks (
  user_id int NOT NULL,
  target_user_id int NOT NULL,
  remark varchar(100) NOT NULL DEFAULT '',
  created_at timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (user_id,target_user_id)
);
CREATE TABLE friend_requests (
  id int PRIMARY KEY NOT NULL AUTO_INCREMENT,
  source int NOT NULL,
  target int NOT NULL,
  sent_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE friendships (
  id int PRIMARY KEY NOT NULL,
  source int NOT NULL,
  target int NOT NULL,
  request_time timestamp NOT NULL,
  allowed_time timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE `groups` (
  id int PRIMARY KEY NOT NULL,
  group_name varchar(100) NOT NULL,
  creator int NOT NULL,
  created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  group_info_permission enum('1','2','3') NOT NULL DEFAULT '1',
  group_avatar varchar(72) NOT NULL DEFAULT '/group_default.png'
);
CREATE TABLE group_members (
  `group` int NOT NULL,
  user int NOT NULL,
  joined_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  role enum('owner','admin','member') NOT NULL DEFAULT 'member',
  group_nickname varchar(100) DEFAULT NULL,
  PRIMARY KEY (`group`,user)
);
CREATE TABLE chats (
  id int PRIMARY KEY NOT NULL AUTO_INCREMENT,
  session int NOT NULL,
  content text NOT NULL,
  multi varchar(100) DEFAULT NULL,
  sent_at timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  sender int NOT NULL,
  type int NOT NULL DEFAULT '1'
);
CREATE TABLE message_read_status (
  user_id int NOT NULL,
  session_id int NOT NULL,
  max_id int NOT NULL,
  PRIMARY KEY (user_id,session_id)
);
CREATE TABLE system_messages (
  id int PRIMARY KEY NOT NULL AUTO_INCREMENT,
  content json NOT NULL,
  sent_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  target int NOT NULL,
  is_read tinyint(1) NOT NULL DEFAULT '0'
);
```

### 服务器配置

#### NGINX

本项目支持使用NGINX作为Web服务器。请确保您的NGINX配置正确，能够正确代理PHP请求并处理静态资源。

#### PHP

本项目使用PHP作为后端语言。请确保您的PHP环境中已经打开了以下扩展：

- **mysqli**：用于连接MySQL数据库。
- **gd**：用于处理图像，例如用户头像的上传和显示。
- **exif**：用于处理图像元数据。
- **mbstring**：用于处理多字节字符串。

### 第三方库配置

- **[jQuery](https://code.jquery.com/jquery-3.7.1.min.js)**：用于前端交互和DOM操作。请存放于`/libs/jquery-3.7.1.js`。
- **[Bootstrap](https://github.com/twbs/bootstrap/releases/download/v5.1.3/bootstrap-5.1.3-dist.zip)**：用于前端样式和布局。请存放于`/libs/bootstrap-5.1.3-dist/`。
- **[Font Awesome](https://github.com/FortAwesome/Font-Awesome/releases/download/5.15.4/fontawesome-free-5.15.4-web.zip)**：用于前端图标。请存放于`/libs/fontawesome-free-5.15.4-web/`。

## 更新日志

### 项目启动日期
2024年7月7日

### 版本1.0.0（2024年9月16日发布）
- 实现了用户注册、登录、修改昵称、修改头像、修改密码、注销账户、查看用户信息和退出登录等核心功能。
- 提供了简洁、易用的用户界面，确保用户能够轻松完成各项操作。

### 版本1.1.0（2024年10月13日发布）
- 新增了设置个人信息的功能，用户可以设置性别、生日以及个人简介。
- 修改了用户ID的设定方式，新用户注册的ID将自动比当前用户最大的ID大1。
- 修改了用户信息修改的接口。

### 版本1.1.1（2024年10月19日发布）
- 修复了绕过前端验证注册非法用户名和密码的漏洞。
- 修复了能修改其他用户个人信息的漏洞。
- 修复了能注销其他用户的漏洞。
- 修复了部分用户头像不显示的漏洞。

### 版本1.1.2（2024年10月25日发布）
- 增强了密码验证机制。
- 确保用户在不同设备上的登录状态同步且安全。
- 不再硬编码数据库配置信息。现在，数据库连接信息（如主机名、用户名、密码、数据库名和表名）被存储在一个外部配置文件中。

### 版本1.2.0（2024年10月26日发布）
- 所有后端接口的返回格式可以为JSON，前提是需要添加v=2键值对。
- 取消了注册成功后的2秒延迟。
- 增加了友好的提示信息。
- 对界面进行了轻微的更新和优化。
- 取消了表名的存放在配置文件中。

### 版本1.3.0（2024年10月31日发布）
- 系统新增多语言支持功能，现已支持繁体中文、英语、法语、俄语、西班牙语、阿拉伯语。

### 版本1.3.1（2024年11月3日发布）
- 修复部分用户无效头像不显示信息的问题。

### 版本2.0.0（2024年11月10日发布）
- 为用户带来了一个全新的界面。
- 新增了好友管理功能，用户现在可以轻松地添加、删除好友，并随时查看好友列表。
- 新增了消息通知系统，现在用户可以查看好友请求、聊天消息等通知。
- 推出了一对一聊天功能，聊天记录会保存在云端。

### 版本2.0.1（2024年11月22日发布）
- 修复了好友列表界面布局问题，优化了名字显示方式，并修正了好友添加逻辑错误。

### 版本2.0.2（2024年11月23日发布）
- 优化聊天界面刷新方式，提升用户体验。

### 版本2.1.0（2024年12月1日发布）
- 新增聊天记录保存为TXT格式功能，并修复删除好友提示信息的多语言支持问题。

### 版本2.1.1（2024年12月7日发布）
- 修复聊天界面按钮间距不一致、无聊天记录时报错问题，并优化页面滚动体验。

### 版本3.0.0（2024年12月15日发布）
- 统一主页顶部字体大小，调整聊天记录显示逻辑，优化聊天界面，修复好友请求、搜索、聊天记录功能及界面布局问题，新增好友聊天文件传输功能。

### 版本4.0.0（2025年7月23日发布）
- 全新界面与功能整合：
  - 全面更新所有页面界面，采用现代化设计风格
  - 将好友列表、好友请求和用户搜索功能整合到主页
  - 引入新的前端库支持，提升用户体验
- 个人主页系统：
  - 新增个人主页功能，可查看其他用户的公开信息
  - 支持自定义个人主页背景图
  - 可为用户设置专属备注（仅自己可见）
- 安全与账户管理增强：
  - 改进登录安全机制：每次登录生成唯一动态Token
  - 新增Token管理中心：
    - 查看当前所有有效Token
    - 自定义Token有效期
    - 支持立即更换Token
    - 可一键下线指定设备
- 群组功能全面升级：
  - 群组创建与管理：
    - 可创建新群组
    - 群主可解散群组或转让群主身份
    - 支持成员主动退出群组
  - 权限分级系统：
    - 三种角色：群主、管理员、普通成员
    - 群主可任命/撤销管理员
    - 群主和管理员可移除成员
  - 群信息控制：
    - 可设置群名称/头像的修改权限（仅群主、管理员或全体成员）
  - 个性化设置：
    - 设置自己在群内的昵称
  - 群聊功能增强：
    - 支持群内文字聊天和文件传输
- 其他改进：
  - 优化移动设备显示效果
  - 修复若干已知问题，提升系统稳定性

## 未来规划

- 完善API文档
- 增强群组管理功能（禁言/投票等）