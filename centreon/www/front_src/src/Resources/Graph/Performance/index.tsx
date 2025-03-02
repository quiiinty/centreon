import {
  MutableRefObject,
  ReactNode,
  useEffect,
  useRef,
  useState
} from 'react';

import { Responsive } from '@visx/visx';
import { useAtomValue } from 'jotai/utils';
import {
  add,
  equals,
  find,
  head,
  isEmpty,
  isNil,
  map,
  negate,
  not,
  or,
  pipe,
  prop,
  propEq,
  propOr,
  reject,
  sortBy
} from 'ramda';
import { useTranslation } from 'react-i18next';
import { makeStyles } from 'tss-react/mui';

import { Skeleton, Typography } from '@mui/material';

import {
  getData,
  timeFormat,
  useLocaleDateTimeFormat,
  useRequest
} from '@centreon/ui';

import { CommentParameters } from '../../Actions/api';
import { selectedResourcesDetailsAtom } from '../../Details/detailsAtoms';
import { ResourceDetails } from '../../Details/models';
import {
  CustomTimePeriod,
  CustomTimePeriodProperty
} from '../../Details/tabs/Graph/models';
import { TimelineEvent } from '../../Details/tabs/Timeline/models';
import { Resource } from '../../models';
import { labelNoDataForThisPeriod } from '../../translatedLabels';

import Graph from './Graph';
import {
  isListingGraphOpenAtom,
  timeValueAtom
} from './Graph/mouseTimeValueAtoms';
import { TimeShiftDirection } from './Graph/TimeShiftZones';
import Legend from './Legend';
import LoadingSkeleton from './LoadingSkeleton';
import {
  AdditionalLines,
  AdjustTimePeriodProps,
  FilterLines,
  GraphData,
  Line as LineModel,
  NewLines,
  TimeValue
} from './models';
import { getLineData, getMetrics, getTimeSeries } from './timeSeries';

interface Props {
  adjustTimePeriod?: (props: AdjustTimePeriodProps) => void;
  customTimePeriod?: CustomTimePeriod;
  displayCompleteGraph?: () => void;
  displayEventAnnotations?: boolean;
  displayTitle?: boolean;
  endpoint?: string;
  filterLines?: ({ lines, resource }: FilterLines) => NewLines;
  getPerformanceGraphRef?: (
    value: MutableRefObject<HTMLDivElement | null>
  ) => void;
  graphActions?: ReactNode;
  graphHeight: number;
  interactWithGraph: boolean;
  isInViewport?: boolean;
  limitLegendRows?: boolean;
  onAddComment?: (commentParameters: CommentParameters) => void;
  renderAdditionalLines?: (args: AdditionalLines) => ReactNode;
  resource: Resource | ResourceDetails;
  resourceDetailsUpdated?: boolean;
  timeline?: Array<TimelineEvent>;
  toggableLegend?: boolean;
  xAxisTickFormat?: string;
}

interface MakeStylesProps extends Pick<Props, 'graphHeight' | 'displayTitle'> {
  canAdjustTimePeriod: boolean;
}

const useStyles = makeStyles<MakeStylesProps>()(
  (theme, { graphHeight, displayTitle, canAdjustTimePeriod }) => ({
    container: {
      display: 'grid',
      flexDirection: 'column',
      gridGap: theme.spacing(0.5),
      gridTemplateRows: `${displayTitle ? 'min-content' : ''} ${theme.spacing(
        2
      )} ${graphHeight}px min-content`,
      height: '100%',
      width: 'auto'
    },
    graphHeader: {
      display: 'grid',
      gridTemplateColumns: '0.4fr 1fr 0.4fr',
      justifyItems: 'end',
      width: '100%'
    },
    graphTranslation: {
      columnGap: theme.spacing(1),
      display: 'grid',
      gridTemplateColumns: canAdjustTimePeriod
        ? 'min-content auto min-content'
        : 'auto',
      justifyContent: canAdjustTimePeriod ? 'space-between' : 'center',
      margin: theme.spacing(0, 1),
      width: '90%'
    },
    loadingContainer: {
      height: theme.spacing(2),
      width: theme.spacing(2)
    },
    noDataContainer: {
      alignItems: 'center',
      display: 'flex',
      height: '100%',
      justifyContent: 'center'
    },
    title: {
      maxWidth: '100%',
      overflow: 'hidden',
      placeSelf: 'center',
      textOverflow: 'ellipsis',
      whiteSpace: 'nowrap'
    }
  })
);

const shiftRatio = 2;

const PerformanceGraph = <T,>({
  endpoint,
  graphHeight,
  xAxisTickFormat = timeFormat,
  toggableLegend = false,
  timeline,
  resource,
  onAddComment,
  adjustTimePeriod,
  customTimePeriod,
  resourceDetailsUpdated = true,
  displayEventAnnotations = false,
  displayTitle = true,
  limitLegendRows,
  isInViewport = true,
  displayCompleteGraph,
  interactWithGraph,
  graphActions,
  getPerformanceGraphRef,
  renderAdditionalLines,
  filterLines
}: Props): JSX.Element => {
  const { classes } = useStyles({
    canAdjustTimePeriod: not(isNil(adjustTimePeriod)),
    displayTitle,
    graphHeight
  });
  const { t } = useTranslation();

  const [timeSeries, setTimeSeries] = useState<Array<TimeValue>>([]);
  const [lineData, setLineData] = useState<Array<LineModel>>();
  const [title, setTitle] = useState<string>();
  const [base, setBase] = useState<number>();

  const performanceGraphRef = useRef<HTMLDivElement | null>(null);
  const performanceGraphHeightRef = useRef<number>(0);

  const {
    sendRequest: sendGetGraphDataRequest,
    sending: sendingGetGraphDataRequest
  } = useRequest<GraphData>({
    request: getData
  });

  const selectedResource = useAtomValue(selectedResourcesDetailsAtom);

  const timeValue = useAtomValue(timeValueAtom);
  const isListingGraphOpen = useAtomValue(isListingGraphOpenAtom);

  const { toDateTime } = useLocaleDateTimeFormat();

  useEffect(() => {
    if (isNil(endpoint)) {
      return;
    }

    sendGetGraphDataRequest({
      endpoint
    })
      .then((graphData) => {
        setTimeSeries(getTimeSeries(graphData));
        setBase(graphData.global.base);
        setTitle(graphData.global.title);
        const newLineData = getLineData(graphData);
        if (lineData) {
          setLineData(
            newLineData.map((line) => ({
              ...line,
              display:
                find(propEq('name', line.name), lineData)?.display ?? true
            }))
          );

          return;
        }

        setLineData(newLineData);
      })
      .catch(() => undefined);
  }, [endpoint]);

  useEffect(() => {
    if (or(isNil(selectedResource?.resourceId), isNil(lineData))) {
      return;
    }
    setLineData(undefined);
  }, [selectedResource?.resourceId]);

  useEffect(() => {
    if (isInViewport && performanceGraphRef.current && lineData) {
      performanceGraphHeightRef.current =
        performanceGraphRef.current.clientHeight;
    }
  }, [isInViewport, lineData]);

  useEffect(() => {
    if (!getPerformanceGraphRef) {
      return;
    }
    getPerformanceGraphRef(performanceGraphRef);
  }, [performanceGraphRef]);

  if (isNil(lineData) || isNil(timeline) || isNil(endpoint)) {
    return (
      <LoadingSkeleton
        displayTitleSkeleton={displayTitle}
        graphHeight={graphHeight}
      />
    );
  }

  if (lineData && not(isInViewport)) {
    return (
      <Skeleton
        height={performanceGraphHeightRef.current}
        variant="rectangular"
        width="100%"
      />
    );
  }

  if (isEmpty(timeSeries) || isEmpty(lineData)) {
    return (
      <div className={classes.noDataContainer}>
        <Typography align="center" variant="body1">
          {t(labelNoDataForThisPeriod)}
        </Typography>
      </div>
    );
  }

  const getLineByMetric = (metric): LineModel => {
    return find(propEq('metric', metric), lineData) as LineModel;
  };

  const toggleMetricLine = (metric): void => {
    const line = getLineByMetric(metric);

    setLineData([
      ...reject(propEq('metric', metric), lineData),
      { ...line, display: !line.display }
    ]);
  };

  const highlightLine = (metric): void => {
    const fadedLines = map((line) => ({ ...line, highlight: false }), lineData);

    setLineData([
      ...reject(propEq('metric', metric), fadedLines),
      { ...getLineByMetric(metric), highlight: true }
    ]);
  };

  const clearHighlight = (): void => {
    setLineData(map((line) => ({ ...line, highlight: undefined }), lineData));
  };

  const filtredLines = filterLines?.({
    lines: lineData,
    resource
  });

  const sortedLines =
    filtredLines?.newSortedLines ?? sortBy(prop('name'), lineData);
  const displayedLines =
    filtredLines?.newLines ?? reject(propEq('display', false), sortedLines);

  const selectMetricLine = (metric: string): void => {
    const metricLine = getLineByMetric(metric);

    const isLineDisplayed = pipe(head, equals(metricLine))(displayedLines);
    const isOnlyLineDisplayed = displayedLines.length === 1 && isLineDisplayed;

    if (isOnlyLineDisplayed || isEmpty(displayedLines)) {
      setLineData(
        map(
          (line) => ({
            ...line,
            display: true
          }),
          lineData
        )
      );

      return;
    }

    setLineData(
      map(
        (line) => ({
          ...line,
          display: equals(line, metricLine)
        }),
        lineData
      )
    );
  };

  const getShiftedDate = ({ property, direction, timePeriod }): Date => {
    const adjustTimePeriodProps =
      (timePeriod.end.getTime() - timePeriod.start.getTime()) / shiftRatio;

    return new Date(
      add(
        prop(property, timePeriod).getTime(),
        equals(direction, TimeShiftDirection.backward)
          ? negate(adjustTimePeriodProps)
          : adjustTimePeriodProps
      )
    );
  };

  const shiftTime = (direction: TimeShiftDirection): void => {
    if (isNil(customTimePeriod)) {
      return;
    }

    adjustTimePeriod?.({
      end: getShiftedDate({
        direction,
        property: CustomTimePeriodProperty.end,
        timePeriod: customTimePeriod
      }),
      start: getShiftedDate({
        direction,
        property: CustomTimePeriodProperty.start,
        timePeriod: customTimePeriod
      })
    });
  };

  const timeTick = propOr<string, TimeValue | null, string>(
    '',
    'timeTick',
    timeValue
  );

  const metrics = getMetrics(timeValue as TimeValue);

  const containsMetrics = not(isNil(metrics)) && not(isEmpty(metrics));

  const isDisplayedInListing = not(displayTitle);

  const displayTimeValues = not(isListingGraphOpen) || isDisplayedInListing;

  return (
    <div
      className={classes.container}
      ref={performanceGraphRef as MutableRefObject<HTMLDivElement | null>}
    >
      {displayTitle && (
        <div className={classes.graphHeader}>
          <div />
          <Typography
            className={classes.title}
            color="textPrimary"
            variant="body1"
          >
            {title}
          </Typography>
          {graphActions}
        </div>
      )}

      <div>
        {displayTimeValues && timeTick && containsMetrics && (
          <Typography align="center" variant="body1">
            {toDateTime(timeTick)}
          </Typography>
        )}
      </div>
      <div>
        <Responsive.ParentSize>
          {({ width, height }): JSX.Element => (
            <Graph<T>
              applyZoom={adjustTimePeriod}
              base={base as number}
              canAdjustTimePeriod={not(isNil(adjustTimePeriod))}
              containsMetrics={containsMetrics}
              displayEventAnnotations={displayEventAnnotations}
              displayTimeValues={displayTimeValues}
              height={height}
              interactWithGraph={interactWithGraph}
              lines={displayedLines}
              loading={
                not(resourceDetailsUpdated) && sendingGetGraphDataRequest
              }
              renderAdditionalLines={renderAdditionalLines}
              resource={resource}
              shiftTime={shiftTime}
              timeSeries={timeSeries}
              timeline={timeline}
              width={width}
              xAxisTickFormat={xAxisTickFormat}
              onAddComment={onAddComment}
            />
          )}
        </Responsive.ParentSize>
      </div>

      <Legend
        base={base as number}
        displayCompleteGraph={displayCompleteGraph}
        displayTimeValues={displayTimeValues}
        limitLegendRows={limitLegendRows}
        lines={sortedLines}
        timeSeries={timeSeries}
        toggable={toggableLegend}
        onClearHighlight={clearHighlight}
        onHighlight={highlightLine}
        onSelect={selectMetricLine}
        onToggle={toggleMetricLine}
      />
    </div>
  );
};

export default PerformanceGraph;
