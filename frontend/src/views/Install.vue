<template>
    <v-container class="fill-height" fluid>
        <v-row justify="center" align="center">
            <v-col cols="12" sm="8" md="6">
                <v-card elevation="10" class="pa-5">
                    <v-alert v-if="alreadyInstalled" type="info" class="mb-4" border="start" prominent>
                        检测到系统已完成安装，请点击下方按钮返回首页。
                    </v-alert>
                    <v-card-title class="text-h6 text-center">
                        初始化安装
                    </v-card-title>

                    <v-card-text>
                        <v-form ref="form">
                            <v-text-field v-model="form.db_host" label="数据库主机 (db_host)" required />
                            <v-text-field v-model="form.db_user" label="数据库用户 (db_user)" required />
                            <v-text-field v-model="form.db_pass" label="数据库密码 (db_pass)" type="password" />
                            <v-text-field v-model="form.db_name" label="数据库名称 (db_name)" required />
                            <v-text-field v-model="form.site_name" label="站点名称 (site_name)" required />
                            <v-text-field v-model="form.site_logo" label="站点 Logo URL (site_logo)" />
                        </v-form>

                        <v-alert v-if="message" :type="messageType" class="mt-4" border="start" prominent>
                            {{ message }}
                        </v-alert>
                    </v-card-text>

                    <v-card-actions class="justify-center">
                        <v-btn v-if="!alreadyInstalled" color="primary" :loading="loading" @click="submitInstall">
                            开始安装
                        </v-btn>
                        <v-btn v-if="!alreadyInstalled" color="secondary" :loading="testing" @click="testConnection">
                            测试数据库连接
                        </v-btn>
                        <v-btn v-if="alreadyInstalled" color="success" @click="$router.push('/')">
                            返回主页
                        </v-btn>
                    </v-card-actions>
                </v-card>
            </v-col>
        </v-row>
    </v-container>
</template>

<script>
const API_BASE = import.meta.env.VITE_API_BASE;

export default {
    data() {
        return {
            loading: false,
            alreadyInstalled: false,
            testing: false,
            message: '',
            messageType: 'success',
            form: {
                db_host: 'localhost',
                db_user: 'root',
                db_pass: '',
                db_name: '',
                site_name: '单品库存管理系统',
                site_logo: '',
            },
        };
    },
    async mounted() {
        try {
            const res = await fetch(`${API_BASE}/api/check-install.php`);
            const result = await res.json();

            if (res.ok && result.installed === true) {
                this.alreadyInstalled = true;
            }
        } catch (e) {
            console.warn('无法判断 install 状态，可能后端未启动：', e);
        }
    },
    methods: {
        async testConnection() {
            this.testing = true;
            this.message = '';
            try {
                const res = await fetch(`${API_BASE}/install/test-db.php`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        db_host: this.form.db_host,
                        db_user: this.form.db_user,
                        db_pass: this.form.db_pass,
                        db_name: this.form.db_name,
                    }),
                });
                const result = await res.json();
                if (result.success) {
                    this.message = '✅ 数据库连接成功！';
                    this.messageType = 'success';
                } else {
                    this.message = '❌ 数据库连接失败：' + result.error;
                    this.messageType = 'error';
                }
            } catch (err) {
                this.message = '连接测试出错：' + err.message;
                this.messageType = 'error';
            }
            this.testing = false;
        },

        async submitInstall() {
            this.loading = true;
            this.message = '';
            try {
                const res = await fetch(`${API_BASE}/install/install.php`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(this.form),
                });
                const result = await res.json();
                if (result.success) {
                    this.message = '🎉 安装成功，2 秒后跳转首页';
                    this.messageType = 'success';
                    setTimeout(() => {
                        this.$router.push('/');
                    }, 2000);
                } else {
                    this.message = '❌ 安装失败：' + result.error;
                    this.messageType = 'error';
                }
            } catch (err) {
                this.message = '安装请求出错：' + err.message;
                this.messageType = 'error';
            }
            this.loading = false;
        },
    },
};
</script>
