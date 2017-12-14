import PaginationMixin from 'src/app/component/mixin/pagination.mixin';
import utils from 'src/core/service/util.service';
import './sw-order-list.less';
import template from './sw-order-list.twig';

Shopware.Component.register('sw-order-list', {
    inject: ['orderService'],
    mixins: [PaginationMixin],

    data() {
        return {
            isWorking: false,
            orderList: [],
            errors: []
        };
    },

    created() {
        this.getData();
    },

    filters: {
        currency: utils.currency,
        date: utils.date
    },

    methods: {
        getData(offset = this.offset, limit = this.limit) {
            this.isWorking = true;
            this.orderService
                .getList(offset, limit)
                .then((response) => {
                    this.orderList = response.data;
                    this.errors = response.errors;
                    this.total = response.total;
                    this.isWorking = false;
                });
        },

        onEdit(order) {
            if (order && order.uuid) {
                this.$router.push({ name: 'sw.order.detail', params: { uuid: order.uuid } });
            }
        },

        handlePagination(offset, limit) {
            this.getData(offset, limit);
        }
    },
    template
});