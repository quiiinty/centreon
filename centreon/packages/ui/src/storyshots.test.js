import initStoryshots from '@storybook/addon-storyshots';
import { imageSnapshot } from '@storybook/addon-storyshots-puppeteer';

jest.unmock('axios');

const getMatchOptions = () => {
  return {
    failureThreshold: 0.2,
    failureThresholdType: 'percent'
  };
};

const beforeScreenshot = async (page) => {
  await page.addStyleTag({
    content: `
    *,
    *::before,
    *::after {
      animation-delay: -1s !important;
      animation-duration: 0s !important;
      animation-iteration-count: 1 !important;
      background-attachment: initial !important;
      scroll-behavior: auto !important;
      transition-duration: 0s !important;
      transition: none !important;
    }`
  });

  await page.setViewport({
    height: 1000,
    width: 1000
  });
};

const getStoryKindRegex = () => {
  const config = JSON.parse(process.env.TEST_CONFIGURATION);
  if (!config.story) {
    return null;
  }

  return new RegExp(`^(${config.story || ''})$`, 'g');
};

initStoryshots({
  storyKindRegex: getStoryKindRegex(),
  suite: 'Image StoryShots',
  test: imageSnapshot({
    beforeScreenshot,
    getMatchOptions,
    storybookUrl: `file://${__dirname}/../.out`
  })
});
