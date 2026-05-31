import { defineStore } from 'pinia';
import { useLoginStore } from './LoginStore';
import axios from '../BaseURL';

export const useCartStore = defineStore('cart', {
    state: () => ({
        cart: JSON.parse(localStorage.getItem("cart")) || [],
        isLoading: false,
        error: null,
    }),

    actions: {
        addToCart(productId, productName, price, type) {
            const existingItem = this.cart.find(item => item.id == productId && item.type == type);
            
            if (existingItem) {
                existingItem.quantity += 1; 
            } else {
                this.cart.push({ id: productId, name: productName, price: price, quantity: 1, type: type});
            }

            this.saveCart();
        },

        addQuantity(productId, type) {
            const item = this.cart.find(item => item.id == productId && item.type == type);
            if (item) {
                item.quantity += 1;
                this.saveCart();
            }
        },

        removeQuantity(productId, type) {
            const item = this.cart.find(item => item.id == productId && item.type == type);
            if (item && item.quantity > 1) {
                item.quantity -= 1;
                this.saveCart();
            }
        },

        removeFromCart(productId, type) {
            this.cart = this.cart.filter(item => item.id !== productId || item.type !== type); // ✅ Remove item
            this.saveCart();
        },

        clearCart() {
            this.cart = [];
            localStorage.removeItem("cart");
        },

        saveCart() {
            localStorage.setItem("cart", JSON.stringify(this.cart)); 
        },

        async checkout() {
            this.isLoading = true;
            try {
                if (this.cart.length === 0) {
                    this.error = "Your cart is empty. Please add some items to your cart before checking out.";
                    this.isLoading = false;
                    return false;
                }
                const login = useLoginStore();
    
                const order = {
                    user_id: login.getUserId,
                    table_number: login.getTableNumber,
                    total_amount: this.getTotal
                }
                const response = await axios.post('/order', order);
                const products = this.getCart;

                for (let item of products) {
                    if (item.type == "food") {
                        await axios.post('/foodorder', { order_id: response.data.id, food_id: item.id, quantity: item.quantity });
                    }
                    else if (item.type == "drink") {
                        await axios.post('/drinkorder', { order_id: response.data.id, drink_id: item.id, quantity: item.quantity });
                    }
                }

                this.clearCart();
                this.isLoading = false;
                return true;
            } catch (error) {
                console.error("Cart Failed:", error);
                this.error = "Something Failed in cart";
                this.isLoading = false;
                return false;
            }
        } 
    },

    getters: {
        getCart: (state) => state.cart, 
        getTotal: (state) => {
            let total = 0;
            state.cart.forEach(item => {
                total += item.price * item.quantity;
            });
            return total.toFixed(2); // ✅ Ensure price has 2 decimal places
        }
    },
});
