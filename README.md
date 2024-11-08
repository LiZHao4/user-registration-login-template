# 用户操作平台项目介绍

## 项目简介

本项目旨在开发一个简洁、实用的用户操作平台，该平台无需依赖任何第三方框架，完全基于原生代码实现。通过此平台，用户可以轻松完成注册、登录、个人信息管理等一系列操作。随着项目的不断发展，我们已陆续推出了多个版本，以不断优化平台功能，提升用户体验。

## 核心功能

1. **注册与登录**
   - 用户可以通过填写必要的注册信息（如用户名、密码等）进行账户注册。
   - 注册成功后，系统将为用户分配一个唯一的用户ID，并记录用户的创建时间。
   - 用户可以使用注册时填写的用户名和密码进行登录，登录成功后即可进入个人信息管理界面。

2. **个人信息管理**
   - 用户可以在个人信息管理界面中修改自己的昵称，以满足个性化需求。
   - 用户可以上传自己的照片作为头像，以增强平台的互动性和趣味性。
   - 用户可以随时在个人信息管理界面中修改自己的登录密码，以确保账户安全。
   - 用户可以设置自己的性别、生日以及个人简介，以丰富个人档案。

3. **账户管理**
   - 对于不再需要使用该平台的用户，可以选择注销自己的账户，以保护个人隐私。
   - 用户可以查看自己的用户ID、账户创建时间、性别、生日、个人简介以及当前登录状态等信息。

4. **退出登录**
   - 为确保用户账户的安全，平台提供了退出登录功能，用户可以随时选择退出当前登录状态。

## 配置要求

### 数据库配置

本项目使用MySQL作为数据库。在您的配置文件中，请确保数据库连接信息正确。以下是我们的用例中的配置信息：

- **用户名**：`yuancheng`
- **密码**：`yc@123456`
- **数据库名**：`yc`
- **数据表名**：`users`

如果您的配置与此不同，请在[配置文件](conf/settings.ini)中相应修改数据库连接信息以及表名等。

#### 数据表结构

为了确保数据的正确存储和访问，您需要创建以下数据表结构：

```sql
CREATE TABLE users (
  id int NOT NULL PRIMARY KEY,
  user varchar(32) NOT NULL,
  password varchar(60) NOT NULL,
  created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  token varchar(32) NOT NULL,
  nick varchar(100) NOT NULL,
  user_avatar tinyint(1) NOT NULL DEFAULT '0',
  gender enum('M','W','N') NOT NULL DEFAULT 'N',
  birth date DEFAULT NULL,
  bio varchar(1024) NOT NULL DEFAULT ''
);
```

### 服务器配置

#### NGINX

本项目支持使用NGINX作为Web服务器。请确保您的NGINX配置正确，能够正确代理PHP请求并处理静态资源。

#### PHP

本项目使用PHP作为后端语言。请确保您的PHP环境中已经打开了以下扩展：

- **mysqli**：用于连接MySQL数据库。
- **gd**：用于处理图像，例如用户头像的上传和显示。

您可以通过修改`php.ini`文件来启用这些扩展。找到`extension=mysqli`和`extension=gd`这两行（如果它们被注释掉了，即前面有`;`），去掉前面的注释符号`;`即可启用。

## 更新日志

### 项目启动日期
2024年7月7日

### 版本1.0.0（2024年9月16日发布）
- 实现了用户注册、登录、修改昵称、修改头像、修改密码、注销账户、查看用户信息和退出登录等核心功能。
- 提供了简洁、易用的用户界面，确保用户能够轻松完成各项操作。

### 版本1.1.0（2024年10月13日发布）
- 新增了设置个人信息的功能，用户可以设置性别、生日以及个人简介。
- 修改了用户ID的设定方式，新用户注册的ID将自动比当前用户最大的ID大1，确保ID的唯一性和有序性。
- 修改了用户信息修改的接口。

### 版本1.1.1（2024年10月19日发布）
- 修复了绕过前端验证注册非法用户名和密码的漏洞，增强了后端验证机制。
- 修复了能修改其他用户个人信息的漏洞，增加了双重验证机制：ID验证和token验证。
- 修复了能注销其他用户的漏洞，同样增加了双重验证机制。
- 修复了部分用户头像不显示的漏洞，提升了用户体验。

### 版本1.1.2（2024年10月25日发布）
- 增强了密码验证机制，提升了用户账户的安全性。
- 优化了多设备登录管理功能，确保用户在不同设备上的登录状态同步且安全。
- 不再硬编码数据库配置信息。现在，数据库连接信息（如主机名、用户名、密码、数据库名和表名）被存储在一个外部配置文件中。

### 版本1.2.0（2024年10月26日发布）
- 所有后端接口的返回格式可以为JSON，便于前端解析和处理数据（前提是需要添加v=2键值对，否则还是和之前一样返回纯文本）。
- 取消了注册成功后的2秒延迟，提高了操作流畅性。
- 增加了友好的提示信息，引导用户完成操作。
- 对界面进行了轻微的更新和优化，提升了整体美观度和用户体验。
- 取消了表名的存放在配置文件中，采用了更安全的配置管理方式。

### 版本1.3.0（2024年10月31日发布）
- 系统新增多语言支持功能，现已支持繁体中文、英语、法语、俄语、西班牙语、阿拉伯语，满足不同国家和地区用户的需求。

### 版本1.3.1（2024年11月3日发布）
- 修复部分用户无效头像不显示信息的问题。

## 未来规划

- 我们将继续优化平台功能，提升用户体验，如增加用户反馈机制、优化登录流程等。
- 同时，我们也将关注平台的安全性，加强密码加密和验证机制，确保用户数据的安全。
- 在未来版本中，我们还将考虑增加更多个性化设置和社交功能，以满足用户的多样化需求。