# 📦 IMS - Inventory Management System

一个为个人单物品无料管理或小团队器材管理设计的现代化库存系统。  
本系统基于 Vue 3 + Vuetify 构建前端，PHP + MySQL 构建后端，支持唯一物品编码、图片上传、出库日志、多状态管理等关键功能。

---

## 🚀 功能概览

- 🆔 **唯一物品编码**（自动生成，如`I250527221138089`）
- 📦 **物品入库管理**：录入名称、来源人、活动、图片等信息
- 🔍 **扫码查询**（支持唯一码 / UPC）
- 🖼️ **图片上传与展示支持**
- ✅ **状态管理**：支持标记为「已赠送」「已丢失」「已使用」
- 🗂️ **物品分类视图**（可按状态切换物品列表）
- 🧾 **出库记录追踪**：记录出入库对象、方式、活动与时间等
- ⚙️ **响应式 UI 体验**：全局提示、卡片展示、移动端适配

---
## 🚀 部署说明（Deployment Instructions）

本项目为一套基于 Vue + PHP + MySQL 的轻量度库存管理系统（IMS）。部署时需分别运行前端与后端服务，并确保 `.env` 设置正确。

---

### 🧱 环境要求（Requirements）

* Node.js 16+
* PHP 7.4+ (含 `mysqli` 扩展)
* MySQL 数据库
* 推荐使用 phpstudy / XAMPP / MAMP 或自建 LAMP 服务器

---

### 📁 项目结构说明

```
ims/
├— backend/              // PHP 后端 API
│   ├— install/          // 安装脚本
│   ├— api/              // 所有接口
│   ├— uploads/          // 上传图片文件夹
│   └— lib/db.php        // 数据库连接配置
└— frontend/             // Vue3 + Vite 前端
    ├— .env.example      // 环境变量模板
    └— src/              // 前端代码
```

---

### 📦 安装步骤（Setup Steps）

#### 1. 克隆仓库

```bash
git clone https://github.com/Doordoorjay/ims.git
cd ims
```

#### 2. 设置前端环境变量

```bash
cd frontend
cp .env.example .env
```

修改 `.env` 中的后端地址为你的后端服务器实际部署地址，例如：

```
VITE_API_BASE=http://localhost:8000
```

#### 3. 安装前端依赖并运行开发环境

```bash
npm install
npm run dev
```

访问前端开发服务器：`http://localhost:5173`

#### 4. 配置后端服务

* 将 `backend/` 部署到本地或远程 PHP 服务器
* 确保 `uploads/` 文件夹存在并具有写权限（`chmod 755` 或 `775`）
* 在浏览器访问 `/install/` 页面或者直接访问主页可自动跳转配置页面，自动创建数据库表并生成 `install.lock`

---

### 🛠️ 常见问题

* **图片无法显示？**
  请检查 `.env` 中的 `VITE_API_BASE` 是否正确，确保访问的是 PHP 后端地址，并确认 `uploads/` 文件夹下确实存在该文件。

---

## 🛠️ 技术栈

### 📦 前端
- [Vue 3](https://vuejs.org/)
- [Vuetify 3](https://next.vuetifyjs.com/)（Material Design UI 框架）
- [Vite](https://vitejs.dev/)

### 🔧 后端
- PHP 7.4+
- MySQL 5.7+/8.0+
- REST API 风格接口设计

### 📁 其他
- 图片上传目录：`/uploads`
- 前后端通信地址通过 `.env` 中的 `VITE_API_BASE` 控制

---

## 📝 TODO List（计划功能）

| 功能                     | 状态     | 说明                                     |
|--------------------------|----------|------------------------------------------|
| 🔐 用户鉴权 / 登录系统   | 💤 计划中 | 支持多用户与角色权限                     |
| 📊 盘点功能              | 💤 计划中 | 可标记盘点时间、导出结果                 |
| 🧾 多条出库日志查看      | 💤 计划中 | 可查看历史记录与出库方式                 |
| 🗃️ 批量导入入库         | 💤 计划中 | 支持通过 Excel/表格快速添加             |
| 🧼 自动标签打印          | 💤 计划中 | 生成二维码或条码标签                     |
| 📱 手机扫码识别          | ✅ 已完成 | 使用摄像头直接扫码查物品                |
| 🌐 多语言界面            | 💤 计划中 | 支持中英文切换（i18n）                   |
| 🔁 操作撤销              | 💤 计划中 | 撤回错误操作或误标状态                   |

---

## 📂 项目结构

```bash
.
├── frontend/             # Vue + Vuetify 前端项目
│   ├── components/       # 所有复用组件（卡片、对话框等）
│   ├── views/            # 页面结构
│   ├── App.vue           # 主入口
│   └── main.js           # Vuetify 初始化
├── backend/              # PHP 后端 API
│   ├── api/              # 所有 API 处理脚本
│   ├── uploads/          # 上传图片存储目录
│   └── lib/db.php        # 数据库连接配置
├── public/               # 静态资源（如 favicon）
├── .env                  # VITE_API_BASE 配置
