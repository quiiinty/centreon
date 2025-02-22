import {
  apiBase,
  applyConfigurationViaClapi,
  checkThatConfigurationIsExported,
  checkThatFixtureServicesExistInDatabase,
  loginAsAdminViaApiV2,
  submitResultsViaClapi,
  versionApi,
  insertFixture
} from '../../commons';

interface Criteria {
  name: string;
  object_type: string | null;
  type: string;
  value: Array<{ id: string; name: string }>;
}

interface Filter {
  criterias: Array<Criteria>;
  name: string;
}

const stateFilterContainer = '[aria-label="State filter"]';
const searchInput = 'input[placeholder="Search"]';
const refreshButton = '[aria-label="Refresh"]';
const resourceMonitoringApi = /.+api\/beta\/monitoring\/resources.?page.+/;

const apiFilterResources = `${apiBase}/${versionApi}/users/filters/events-view`;

const initializeResourceData = (): Cypress.Chainable => {
  const files = [
    'resources/clapi/host1/01-add.json',
    'resources/clapi/service1/01-add.json',
    'resources/clapi/service1/02-set-max-check.json',
    'resources/clapi/service1/03-disable-active-check.json',
    'resources/clapi/service1/04-enable-passive-check.json',
    'resources/clapi/service2/01-add.json',
    'resources/clapi/service2/02-set-max-check.json',
    'resources/clapi/service2/03-disable-active-check.json',
    'resources/clapi/service2/04-enable-passive-check.json',
    'resources/clapi/service3/01-add.json',
    'resources/clapi/service3/02-set-max-check.json',
    'resources/clapi/service3/03-disable-active-check.json',
    'resources/clapi/service3/04-enable-passive-check.json'
  ];

  return cy.wrap(Promise.all(files.map(insertFixture)));
};

const insertResourceFixtures = (): Cypress.Chainable => {
  const dateBeforeLogin = new Date();

  return loginAsAdminViaApiV2()
    .then(initializeResourceData)
    .then(applyConfigurationViaClapi)
    .then(() => checkThatConfigurationIsExported({ dateBeforeLogin }))
    .then(submitResultsViaClapi)
    .then(checkThatFixtureServicesExistInDatabase);
};

const setUserFilter = (body: Filter): Cypress.Chainable => {
  return cy
    .request({
      body,
      method: 'POST',
      url: apiFilterResources
    })
    .then((response) => {
      expect(response.status).to.eq(200);
      customFilterId = response.body.id;
    });
};

const deleteUserFilter = (): Cypress.Chainable => {
  if (customFilterId === null) {
    return cy.wrap({});
  }

  return cy
    .request({
      method: 'DELETE',
      url: `${apiFilterResources}/${customFilterId}`
    })
    .then((response) => {
      expect(response.status).to.eq(204);
      customFilterId = null;
    });
};

const tearDownResource = (): Cypress.Chainable => {
  return cy
    .setUserTokenApiV1()
    .then(() => cy.removeResourceData())
    .then(applyConfigurationViaClapi);
};

const actionBackgroundColors = {
  acknowledge: 'rgb(245, 241, 233)',
  inDowntime: 'rgb(240, 233, 248)'
};
const actions = {
  acknowledge: 'Acknowledge',
  setDowntime: 'Set downtime'
};

let customFilterId = null;

export {
  stateFilterContainer,
  searchInput,
  refreshButton,
  resourceMonitoringApi,
  actionBackgroundColors,
  actions,
  insertResourceFixtures,
  setUserFilter,
  deleteUserFilter,
  tearDownResource
};
