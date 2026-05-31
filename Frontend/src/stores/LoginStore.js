import { defineStore } from 'pinia';
import axios from '../BaseURL';
import { useCartStore } from './CartStore';

export const useLoginStore = defineStore('login', {
    state: () => ({
        jwt: localStorage.getItem("jwt") || "",
        refreshToken: localStorage.getItem("refreshToken") || "",
        userId: localStorage.getItem("userId") || null,
        tablenumber: localStorage.getItem("tableNumber") || null,
        role: localStorage.getItem("role") || "",
        error: null,
        isLoading: false
    }),

    actions: {
        async login(email, password) {
            this.isLoading = true;
            try {
                const response = await axios.post(`http://localhost/login`, {
                    email,
                    password
                });
                const token = response.data.jwt;
                if (!token) {
                    throw new Error("Missing JWT token"); // force it into catch
                }
                const refreshToken = response.data.refreshtoken;
                const userId = response.data.userid;
                const role = response.data.role;

                this.role = role;
                this.jwt = token;
                this.refreshToken = refreshToken;
                this.userId = userId;

                localStorage.setItem("jwt", token);
                localStorage.setItem("refreshToken", refreshToken);
                localStorage.setItem("userId", userId);
                localStorage.setItem("role", role);

                axios.defaults.headers.common['Authorization'] = "Bearer " + token;

                console.log("Login Successful");
                this.isLoading = false;
                return true;
            } catch (error) {
                console.error("Login Failed:", error);
                this.error = "Login Failed";
                this.isLoading = false;
                return false;
            }
        },

        async refreshAccessToken() {
            const refreshToken = localStorage.getItem("refreshToken");
            const userid = localStorage.getItem("userId");
            if (!refreshToken) return null;
        
            try {
                const response = await axios.post("/refresh", {
                    id: userid,
                    refreshtoken: refreshToken
                });
        
                const newAccessToken = response.data.jwt;
                const newRefreshToken = response.data.refreshtoken;
                const userId = response.data.userid;
                const role = response.data.role;

                this.role = role;
                this.jwt = newAccessToken;
                this.refreshToken = newRefreshToken;
                this.userId = userId;
        
                localStorage.setItem("role", role);
                localStorage.setItem("jwt", newAccessToken);
                localStorage.setItem("refreshToken", newRefreshToken);
                localStorage.setItem("userId", userId);

                axios.defaults.headers.common['Authorization'] = "Bearer " + newAccessToken;
        
                return true;
            } catch (err) {
                console.error("Token refresh failed:", err);
                return null;
            }
        },

        setTableNumber(tableNumber) {
            this.tablenumber = tableNumber;
            localStorage.setItem("tableNumber", tableNumber);
        },

        logout() {
            this.jwt = "";
            this.refreshToken = "";
            this.userId = null;
            this.tablenumber = null;
            this.role = "";

            localStorage.removeItem("role");
            localStorage.removeItem("jwt");
            localStorage.removeItem("refreshToken");
            localStorage.removeItem("userId");
            localStorage.removeItem("tableNumber");

            delete axios.defaults.headers.common['Authorization'];

            const cart = useCartStore();
            cart.clearCart();
            //window.location.href = '/login';
        }
    },

    getters: {
        isLoggedIn: (state) => !!state.jwt,
        getUserId: (state) => state.userId,
        getRefreshToken: (state) => state.refreshToken,
        getTableNumber: (state) => state.tablenumber,
        getRole: (state) => state.role,
    }
});
