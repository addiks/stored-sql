/** @type {import('ts-jest/dist/types').InitialOptionsTsJest} */
module.exports = {
  preset: 'ts-jest',
  testEnvironment: 'node',
  moduleNameMapper: {
    "storedsql": "<rootDir>/ts/storedsql"
  },
  "transform": {
    "\\.twig$": "<rootDir>/ts/twig-jest-transform.js"
  }
};
