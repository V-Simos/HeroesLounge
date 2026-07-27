'use strict';

const assert = require('assert');
const path = require('path');

class FakeClassList {
    constructor(initial) {
        this.values = new Set(initial || []);
    }

    contains(name) {
        return this.values.has(name);
    }

    toggle(name, force) {
        const enabled = force === undefined ? !this.values.has(name) : Boolean(force);
        if (enabled) this.values.add(name);
        else this.values.delete(name);
        return enabled;
    }
}

class FakeElement {
    constructor(options) {
        const config = options || {};
        this.id = config.id || '';
        this.role = config.role || '';
        this.dataset = config.target ? { tabTarget: config.target } : {};
        this.hidden = Boolean(config.hidden);
        this.disabled = Boolean(config.disabled);
        this.ownerTabs = config.ownerTabs || null;
        this.parentElement = config.parentElement || null;
        this.attributes = new Map();
        this.classList = new FakeClassList(config.on ? ['on'] : []);

        if (this.id) this.attributes.set('id', this.id);
        if (this.role) this.attributes.set('role', this.role);
        if (config.selected !== undefined) {
            this.attributes.set('aria-selected', config.selected ? 'true' : 'false');
        }
        if (config.tabindex !== undefined) {
            this.attributes.set('tabindex', String(config.tabindex));
        }
    }

    closest(selector) {
        if (selector === '[data-tabs]') return this.ownerTabs;
        if (selector === '[role="tab"][data-tab-target]') {
            if (this.role === 'tab' && this.dataset.tabTarget) return this;
            return this.parentElement ? this.parentElement.closest(selector) : null;
        }
        return null;
    }

    getAttribute(name) {
        return this.attributes.has(name) ? this.attributes.get(name) : null;
    }

    setAttribute(name, value) {
        this.attributes.set(name, String(value));
    }

    focus() {
        fakeDocument.activeElement = this;
    }
}

class FakeTabSet extends FakeElement {
    constructor(name) {
        super({ id: name });
        this.ownerTabs = this;
        this.tabs = [];
        this.descendantTabs = [];
        this.listeners = new Map();
    }

    querySelectorAll(selector) {
        if (selector === '[role="tab"][data-tab-target]') return this.descendantTabs;
        return [];
    }

    addEventListener(type, listener) {
        this.listeners.set(type, listener);
    }

    dispatch(type, target, key, existingEvent) {
        const event = existingEvent || {
            type,
            target,
            key,
            defaultPrevented: false,
            preventDefault() {
                this.defaultPrevented = true;
            }
        };
        const listener = this.listeners.get(type);
        if (listener) listener(event);
        return event;
    }
}

class FakeDocument {
    constructor() {
        this.activeElement = null;
        this.elements = new Map();
        this.tabSets = [];
    }

    register(element) {
        if (!element.id) return;
        if (this.elements.has(element.id)) {
            throw new Error(`Duplicate fixture id: ${element.id}`);
        }
        this.elements.set(element.id, element);
    }

    querySelectorAll(selector) {
        if (selector === '[data-tabs]') return this.tabSets;
        if (selector === '[data-countdown]') return [];
        return [];
    }

    getElementById(id) {
        return this.elements.get(id) || null;
    }
}

const fakeDocument = new FakeDocument();

function createTabSet(name, definitions) {
    const tabSet = new FakeTabSet(name);
    const panels = [];

    definitions.forEach((definition) => {
        const tabId = `${name}-${definition.key}-tab`;
        const panelId = `${name}-${definition.key}-panel`;
        const tab = new FakeElement({
            id: tabId,
            role: 'tab',
            target: panelId,
            ownerTabs: tabSet,
            selected: Boolean(definition.selected),
            tabindex: definition.selected ? 0 : -1,
            on: Boolean(definition.selected),
            hidden: Boolean(definition.hidden),
            disabled: Boolean(definition.disabled)
        });
        const panel = new FakeElement({
            id: panelId,
            role: 'tabpanel',
            hidden: !definition.selected
        });
        tabSet.tabs.push(tab);
        tabSet.descendantTabs.push(tab);
        panels.push(panel);
        fakeDocument.register(tab);
        fakeDocument.register(panel);
    });

    fakeDocument.tabSets.push(tabSet);
    return { tabSet, tabs: tabSet.tabs, panels };
}

function selectedTab(fixture) {
    return fixture.tabs.filter((tab) => tab.getAttribute('aria-selected') === 'true');
}

function focusableTab(fixture) {
    return fixture.tabs.filter((tab) => tab.getAttribute('tabindex') === '0');
}

function visiblePanel(fixture) {
    return fixture.panels.filter((panel) => !panel.hidden);
}

function assertSingleState(fixture, expectedIndex) {
    const expectedTabId = fixture.tabs[expectedIndex].id;
    const expectedPanelId = fixture.panels[expectedIndex].id;
    assert.deepStrictEqual(selectedTab(fixture).map((tab) => tab.id), [expectedTabId]);
    assert.deepStrictEqual(focusableTab(fixture).map((tab) => tab.id), [expectedTabId]);
    assert.deepStrictEqual(visiblePanel(fixture).map((panel) => panel.id), [expectedPanelId]);
}

const basic = createTabSet('basic', [
    { key: 'one', selected: true },
    { key: 'two' },
    { key: 'three' }
]);

const skipUnavailable = createTabSet('skip', [
    { key: 'one', selected: true },
    { key: 'disabled', disabled: true },
    { key: 'hidden', hidden: true },
    { key: 'four' }
]);

const unavailableSelected = createTabSet('unavailable-selected', [
    { key: 'one' },
    { key: 'hidden', hidden: true, selected: true },
    { key: 'three' }
]);

const outer = createTabSet('outer', [
    { key: 'one', selected: true },
    { key: 'two' }
]);
const inner = createTabSet('inner', [
    { key: 'one', selected: true },
    { key: 'two' }
]);
outer.tabSet.descendantTabs.push(...inner.tabs);

global.document = fakeDocument;
global.window = {};

require(path.resolve(__dirname, '..', 'themes', 'heroeslounge-next', 'assets', 'js', 'lounge.js'));

const tests = [];

function test(name, callback) {
    tests.push({ name, callback });
}

test('initialization creates one selected, focusable, visible tab-panel state', () => {
    assertSingleState(basic, 0);
});

test('initialization ignores a selected tab that is hidden', () => {
    assertSingleState(unavailableSelected, 0);
});

test('ArrowRight and ArrowLeft wrap while skipping hidden and disabled tabs', () => {
    let event = skipUnavailable.tabSet.dispatch('keydown', skipUnavailable.tabs[0], 'ArrowRight');
    assert.strictEqual(event.defaultPrevented, true);
    assertSingleState(skipUnavailable, 3);
    assert.strictEqual(fakeDocument.activeElement, skipUnavailable.tabs[3]);

    event = skipUnavailable.tabSet.dispatch('keydown', skipUnavailable.tabs[3], 'ArrowRight');
    assert.strictEqual(event.defaultPrevented, true);
    assertSingleState(skipUnavailable, 0);

    event = skipUnavailable.tabSet.dispatch('keydown', skipUnavailable.tabs[0], 'ArrowLeft');
    assert.strictEqual(event.defaultPrevented, true);
    assertSingleState(skipUnavailable, 3);
});

test('Home and End activate and focus the first and last available tabs', () => {
    skipUnavailable.tabSet.dispatch('keydown', skipUnavailable.tabs[3], 'Home');
    assertSingleState(skipUnavailable, 0);
    assert.strictEqual(fakeDocument.activeElement, skipUnavailable.tabs[0]);

    skipUnavailable.tabSet.dispatch('keydown', skipUnavailable.tabs[0], 'End');
    assertSingleState(skipUnavailable, 3);
    assert.strictEqual(fakeDocument.activeElement, skipUnavailable.tabs[3]);
});

test('click activation prevents form submission and preserves nested isolation', () => {
    const click = inner.tabSet.dispatch('click', inner.tabs[1]);
    outer.tabSet.dispatch('click', inner.tabs[1], undefined, click);

    assert.strictEqual(click.defaultPrevented, true);
    assertSingleState(inner, 1);
    assertSingleState(outer, 0);
});

let failures = 0;
tests.forEach(({ name, callback }) => {
    try {
        callback();
        process.stdout.write(`PASS ${name}\n`);
    }
    catch (error) {
        failures++;
        process.stderr.write(`FAIL ${name}\n${error.stack}\n`);
    }
});

if (failures) {
    process.stderr.write(`${failures} tab behavior contract(s) failed.\n`);
    process.exit(1);
}

process.stdout.write(`${tests.length} tab behavior contracts pass.\n`);
