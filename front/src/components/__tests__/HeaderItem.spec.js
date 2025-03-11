import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import HeaderItem from '../layout/HeaderItem.vue';

describe('HeaderItem', () => {
    it('renders properly', () => {
        const wrapper = mount(HeaderItem);
        expect(wrapper.exists()).toBe(true);
    });
});
