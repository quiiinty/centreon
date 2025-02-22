import {
  memo,
  MouseEvent,
  ReactNode,
  useEffect,
  useMemo,
  useRef,
  useState
} from 'react';

import { AddSVGProps } from '@visx/shape/lib/types';
import { Event, Grid, Group, Shape, Tooltip as VisxTooltip } from '@visx/visx';
import { bisector } from 'd3-array';
import { useAtomValue, useUpdateAtom } from 'jotai/utils';
import { equals, gte, identity, isNil, lt, not, pick, values } from 'ramda';
import { useTranslation } from 'react-i18next';
import { makeStyles } from 'tss-react/mui';

import {
  alpha,
  Button,
  CircularProgress,
  ClickAwayListener,
  Paper,
  Tooltip,
  Typography,
  useTheme
} from '@mui/material';
import { grey } from '@mui/material/colors';

import {
  dateTimeFormat,
  useLocaleDateTimeFormat,
  useMemoComponent
} from '@centreon/ui';

import { CommentParameters } from '../../../Actions/api';
import useAclQuery from '../../../Actions/Resource/aclQuery';
import { ResourceDetails } from '../../../Details/models';
import { TimelineEvent } from '../../../Details/tabs/Timeline/models';
import { Resource } from '../../../models';
import {
  labelActionNotPermitted,
  labelAddComment
} from '../../../translatedLabels';
import Lines from '../Lines';
import {
  AdditionalLines,
  AdjustTimePeriodProps,
  Line as LineModel,
  TimeValue
} from '../models';
import {
  getDates,
  getLeftScale,
  getRightScale,
  getSortedStackedLines,
  getTime,
  getUnits,
  getXScale,
  getYScale
} from '../timeSeries';

import AddCommentForm from './AddCommentForm';
import Annotations from './Annotations';
import {
  annotationHoveredAtom,
  changeAnnotationHoveredDerivedAtom
} from './annotationsAtoms';
import Axes from './Axes';
import {
  changeMousePositionAndTimeValueDerivedAtom,
  changeTimeValueDerivedAtom,
  MousePosition,
  mousePositionAtom
} from './mouseTimeValueAtoms';
import TimeShiftZones, {
  TimeShiftContext,
  TimeShiftDirection
} from './TimeShiftZones';

interface BarProps {
  className?: string;
  innerRef?: React.Ref<SVGRectElement>;
  open: boolean;
}

const Bar = ({
  open,
  ...restProps
}: AddSVGProps<BarProps, SVGRectElement>): JSX.Element | null => {
  if (!open) {
    return null;
  }

  return <Shape.Bar {...restProps} />;
};

const propsAreEqual = (prevProps, nextProps): boolean =>
  equals(prevProps, nextProps);

const MemoizedAxes = memo(Axes, propsAreEqual);
const MemoizedBar = memo(Bar, propsAreEqual);
const MemoizedGridColumns = memo(Grid.GridColumns, propsAreEqual);
const MemoizedGridRows = memo(Grid.GridRows, propsAreEqual);
const MemoizedLines = memo(Lines, propsAreEqual);
const MemoizedAnnotations = memo(Annotations, propsAreEqual);

const margin = { bottom: 30, left: 45, right: 45, top: 30 };

const commentTooltipWidth = 165;

interface Props {
  base: number;
  height: number;
  lines: Array<LineModel>;
  onAddComment?: (commentParameters: CommentParameters) => void;
  resource: Resource | ResourceDetails;
  timeSeries: Array<TimeValue>;
  timeline?: Array<TimelineEvent>;
  width: number;
  xAxisTickFormat: string;
}

const useStyles = makeStyles<Pick<Props, 'onAddComment'>>()(
  (theme, { onAddComment }) => ({
    addCommentButton: {
      fontSize: 10
    },
    addCommentTooltip: {
      display: 'grid',
      fontSize: 10,
      gridAutoFlow: 'row',
      justifyItems: 'center',
      padding: theme.spacing(0.5),
      position: 'absolute'
    },
    container: {
      '& .visx-axis-bottom': {
        '& .visx-axis-tick': {
          '& .visx-line': {
            stroke: theme.palette.text.primary
          }
        }
      },
      '& .visx-axis-line': {
        stroke: theme.palette.text.primary
      },
      '& .visx-axis-right': {
        '& .visx-axis-tick': {
          '& .visx-line': {
            stroke: theme.palette.text.primary
          }
        }
      },
      '& .visx-columns': {
        '& .visx-line': {
          stroke: theme.palette.divider
        }
      },
      '& .visx-rows': {
        '& .visx-line': {
          stroke: theme.palette.divider
        }
      },
      fill: theme.palette.text.primary,
      position: 'relative'
    },
    graphLoader: {
      alignItems: 'center',
      backgroundColor: alpha(theme.palette.common.white, 0.5),
      display: 'flex',
      height: '100%',
      justifyContent: 'center',
      position: 'absolute',
      width: '100%'
    },
    overlay: {
      cursor: isNil(onAddComment) ? 'normal' : 'crosshair'
    },
    tooltip: {
      padding: 12,
      zIndex: theme.zIndex.tooltip
    }
  })
);

interface ZoomBoundaries {
  end: number;
  start: number;
}

interface GraphContentProps {
  addCommentTooltipLeft?: number;
  addCommentTooltipOpen: boolean;
  addCommentTooltipTop?: number;
  applyZoom?: (props: AdjustTimePeriodProps) => void;
  base: number;
  canAdjustTimePeriod: boolean;
  containsMetrics: boolean;
  displayEventAnnotations: boolean;
  displayTimeValues: boolean;
  format: (parameters) => string;
  height: number;
  hideAddCommentTooltip: () => void;
  interactWithGraph: boolean;
  isInViewport?: boolean;
  lines: Array<LineModel>;
  loading: boolean;
  onAddComment?: (commentParameters: CommentParameters) => void;
  renderAdditionalLines?: (args: AdditionalLines) => ReactNode;
  resource: Resource | ResourceDetails;
  shiftTime?: (direction: TimeShiftDirection) => void;
  showAddCommentTooltip: (args) => void;
  timeSeries: Array<TimeValue>;
  timeline?: Array<TimelineEvent>;
  width: number;
  xAxisTickFormat: string;
}

export const bisectDate = bisector(identity).center;

const GraphContent = ({
  width,
  height,
  timeSeries,
  base,
  lines,
  xAxisTickFormat,
  timeline,
  resource,
  addCommentTooltipLeft,
  addCommentTooltipTop,
  addCommentTooltipOpen,
  onAddComment,
  hideAddCommentTooltip,
  showAddCommentTooltip,
  format,
  applyZoom,
  shiftTime,
  loading,
  canAdjustTimePeriod,
  displayEventAnnotations,
  containsMetrics,
  isInViewport,
  interactWithGraph,
  displayTimeValues,
  renderAdditionalLines
}: GraphContentProps): JSX.Element => {
  const { classes } = useStyles({ onAddComment });
  const { t } = useTranslation();
  const theme = useTheme();

  const [addingComment, setAddingComment] = useState(false);
  const [commentDate, setCommentDate] = useState<Date>();
  const [zoomPivotPosition, setZoomPivotPosition] = useState<number | null>(
    null
  );
  const [zoomBoundaries, setZoomBoundaries] = useState<ZoomBoundaries | null>(
    null
  );
  const graphSvgRef = useRef<SVGSVGElement | null>(null);
  const { canComment } = useAclQuery();
  const mousePosition = useAtomValue(mousePositionAtom);
  const changeMousePositionAndTimeValue = useUpdateAtom(
    changeMousePositionAndTimeValueDerivedAtom
  );
  const changeTimeValue = useUpdateAtom(changeTimeValueDerivedAtom);
  const setAnnotationHovered = useUpdateAtom(annotationHoveredAtom);
  const changeAnnotationHovered = useUpdateAtom(
    changeAnnotationHoveredDerivedAtom
  );

  const graphWidth = width > 0 ? width - margin.left - margin.right : 0;
  const graphHeight = height > 0 ? height - margin.top - margin.bottom : 0;

  const hideAddCommentTooltipOnEspcapePress = (
    event: globalThis.KeyboardEvent
  ): void => {
    if (event.key === 'Escape') {
      hideAddCommentTooltip();
    }
  };

  useEffect(() => {
    document.addEventListener(
      'keydown',
      hideAddCommentTooltipOnEspcapePress,
      false
    );

    return (): void => {
      document.removeEventListener(
        'keydown',
        hideAddCommentTooltipOnEspcapePress,
        false
      );
    };
  }, []);

  const xScale = useMemo(
    () =>
      getXScale({
        dataTime: timeSeries,
        valueWidth: graphWidth
      }),
    [graphWidth, timeSeries]
  );

  const leftScale = useMemo(
    () =>
      getLeftScale({
        dataLines: lines,
        dataTimeSeries: timeSeries,
        valueGraphHeight: graphHeight
      }),
    [timeSeries, lines, graphHeight]
  );

  const rightScale = useMemo(
    () =>
      getRightScale({
        dataLines: lines,
        dataTimeSeries: timeSeries,
        valueGraphHeight: graphHeight
      }),
    [timeSeries, lines, graphHeight]
  );

  const getTimeValue = (x: number): TimeValue => {
    const date = xScale.invert(x - margin.left);
    const index = bisectDate(getDates(timeSeries), date);

    return timeSeries[index];
  };

  const updateMousePosition = (position: MousePosition): void => {
    if (isNil(position)) {
      changeMousePositionAndTimeValue({
        position: null,
        timeValue: null
      });

      return;
    }
    const timeValue = getTimeValue(position[0]);

    changeMousePositionAndTimeValue({ position, timeValue });
  };

  const displayTooltip = (event: MouseEvent<SVGRectElement>): void => {
    const { x, y } = Event.localPoint(
      graphSvgRef.current as SVGSVGElement,
      event
    ) || { x: 0, y: 0 };

    const mouseX = x - margin.left;

    changeAnnotationHovered({
      graphWidth,
      mouseX,
      resourceId: resource.uuid,
      timeline,
      xScale
    });

    if (zoomPivotPosition) {
      setZoomBoundaries({
        end: gte(mouseX, zoomPivotPosition) ? mouseX : zoomPivotPosition,
        start: lt(mouseX, zoomPivotPosition) ? mouseX : zoomPivotPosition
      });
      changeTimeValue({ isInViewport, newTimeValue: null });

      return;
    }

    const position: MousePosition = [x, y];

    updateMousePosition(position);
  };

  const closeZoomPreview = (): void => {
    setZoomBoundaries(null);
    setZoomPivotPosition(null);
  };

  const closeTooltip = (): void => {
    updateMousePosition(null);
    setAnnotationHovered(undefined);

    if (not(isNil(zoomPivotPosition))) {
      return;
    }
    closeZoomPreview();
  };

  const displayAddCommentTooltip = (event): void => {
    setZoomBoundaries(null);
    setZoomPivotPosition(null);
    if (isNil(onAddComment)) {
      return;
    }

    if (zoomBoundaries?.start !== zoomBoundaries?.end) {
      applyZoom?.({
        end: xScale.invert(zoomBoundaries?.end || graphWidth),
        start: xScale.invert(zoomBoundaries?.start || 0)
      });

      return;
    }

    const { x, y } = Event.localPoint(event) || { x: 0, y: 0 };

    const { timeTick } = getTimeValue(x);
    const date = new Date(timeTick);

    setCommentDate(date);

    const displayLeft = width - x < commentTooltipWidth;

    showAddCommentTooltip({
      tooltipLeft: displayLeft ? x - commentTooltipWidth : x,
      tooltipTop: y
    });
  };

  const prepareAddComment = (): void => {
    setAddingComment(true);
    hideAddCommentTooltip();
  };

  const confirmAddComment = (comment): void => {
    setAddingComment(false);
    onAddComment?.(comment);
  };

  const displayZoomPreview = (event): void => {
    if (isNil(onAddComment)) {
      return;
    }
    const { x } = Event.localPoint(event) || { x: 0 };

    const mouseX = x - margin.left;

    setZoomPivotPosition(mouseX);
    setZoomBoundaries({
      end: mouseX,
      start: mouseX
    });
    hideAddCommentTooltip();
  };

  const position = mousePosition;

  const mousePositionX = (position?.[0] || 0) - margin.left;
  const mousePositionY = (position?.[1] || 0) - margin.top;

  const zoomBarWidth = Math.abs(
    (zoomBoundaries?.end || 0) - (zoomBoundaries?.start || 0)
  );

  const mousePositionTimeTick = position
    ? getTimeValue(position[0]).timeTick
    : 0;

  const timeTick = containsMetrics ? new Date(mousePositionTimeTick) : null;

  const isCommentPermitted = canComment([resource]);

  const commentTitle = isCommentPermitted ? '' : t(labelActionNotPermitted);

  const additionalLinesProps = {
    getSortedStackedLines,
    getTime,
    getUnits,
    getYScale,
    graphHeight,
    graphWidth,
    leftScale,
    lines,
    rightScale,
    timeSeries,
    xScale
  };

  return (
    <ClickAwayListener onClickAway={hideAddCommentTooltip}>
      <div className={classes.container}>
        {loading && (
          <div className={classes.graphLoader}>
            <CircularProgress />
          </div>
        )}
        <svg
          height={height}
          ref={graphSvgRef}
          width="100%"
          onMouseUp={closeZoomPreview}
        >
          <Group.Group left={margin.left} top={margin.top}>
            <MemoizedGridRows
              height={graphHeight}
              scale={rightScale || leftScale}
              width={graphWidth}
            />
            <MemoizedGridColumns
              height={graphHeight}
              scale={xScale}
              width={graphWidth}
            />
            <MemoizedAxes
              base={base}
              graphHeight={graphHeight}
              graphWidth={graphWidth}
              leftScale={leftScale}
              lines={lines}
              rightScale={rightScale}
              xAxisTickFormat={xAxisTickFormat}
              xScale={xScale}
            />
            <MemoizedLines
              displayTimeValues={displayTimeValues}
              graphHeight={graphHeight}
              leftScale={leftScale}
              lines={lines}
              renderAdditionalLines={renderAdditionalLines?.({
                additionalLinesProps,
                resource
              })}
              rightScale={rightScale}
              timeSeries={timeSeries}
              timeTick={timeTick}
              xScale={xScale}
            />

            {displayEventAnnotations && (
              <MemoizedAnnotations
                graphHeight={graphHeight}
                resourceId={resource.uuid}
                timeline={timeline as Array<TimelineEvent>}
                xScale={xScale}
              />
            )}

            <MemoizedBar
              fill={alpha(theme.palette.primary.main, 0.2)}
              height={graphHeight}
              open={interactWithGraph}
              stroke={alpha(theme.palette.primary.main, 0.5)}
              width={zoomBarWidth}
              x={zoomBoundaries?.start || 0}
              y={0}
            />
            {useMemoComponent({
              Component:
                displayTimeValues && containsMetrics && position ? (
                  <g>
                    <Shape.Line
                      from={{ x: mousePositionX, y: 0 }}
                      pointerEvents="none"
                      stroke={grey[400]}
                      strokeWidth={1}
                      to={{ x: mousePositionX, y: graphHeight }}
                    />
                    <Shape.Line
                      from={{ x: 0, y: mousePositionY }}
                      pointerEvents="none"
                      stroke={grey[400]}
                      strokeWidth={1}
                      to={{ x: graphWidth, y: mousePositionY }}
                    />
                  </g>
                ) : (
                  <g />
                ),
              memoProps: [mousePosition]
            })}
            <MemoizedBar
              className={classes.overlay}
              fill="transparent"
              height={graphHeight}
              open={interactWithGraph}
              width={graphWidth}
              x={0}
              y={0}
              onMouseDown={displayZoomPreview}
              onMouseLeave={closeTooltip}
              onMouseMove={displayTooltip}
              onMouseUp={displayAddCommentTooltip}
            />
          </Group.Group>
          <TimeShiftContext.Provider
            value={useMemo(
              () => ({
                canAdjustTimePeriod,
                graphHeight,
                graphWidth,
                loading,
                marginLeft: margin.left,
                marginTop: margin.top,
                shiftTime
              }),
              [
                canAdjustTimePeriod,
                graphHeight,
                graphWidth,
                loading,
                margin,
                shiftTime
              ]
            )}
          >
            <TimeShiftZones />
          </TimeShiftContext.Provider>
        </svg>
        {addCommentTooltipOpen && (
          <Paper
            className={classes.addCommentTooltip}
            style={{
              left: addCommentTooltipLeft,
              top: addCommentTooltipTop,
              width: commentTooltipWidth
            }}
          >
            <Typography variant="caption">
              {format({
                date: new Date(commentDate as Date),
                formatString: dateTimeFormat
              })}
            </Typography>
            <Tooltip title={commentTitle}>
              <div>
                <Button
                  className={classes.addCommentButton}
                  color="primary"
                  disabled={!isCommentPermitted}
                  size="small"
                  onClick={prepareAddComment}
                >
                  {t(labelAddComment)}
                </Button>
              </div>
            </Tooltip>
          </Paper>
        )}
        {addingComment && (
          <AddCommentForm
            date={commentDate as Date}
            resource={resource}
            onClose={(): void => {
              setAddingComment(false);
            }}
            onSuccess={confirmAddComment}
          />
        )}
      </div>
    </ClickAwayListener>
  );
};

const propertiesToMemoize = [
  'addCommentTooltipLeft',
  'addCommentTooltipTop',
  'addCommentTooltipOpen',
  'width',
  'height',
  'timeSeries',
  'base',
  'lines',
  'xAxisTickFormat',
  'timeline',
  'resource',
  'loading',
  'canAdjustTimePeriod',
  'displayTooltipValues',
  'displayEventAnnotations',
  'containsMetrics',
  'isInViewport'
];

const Graph = (
  props: Omit<
    GraphContentProps,
    | 'addCommentTooltipLeft'
    | 'addCommentTooltipTop'
    | 'addCommentTooltipOpen'
    | 'showAddCommentTooltip'
    | 'hideAddCommentTooltip'
    | 'format'
    | 'changeMetricsValue'
    | 'isInViewport'
  >
): JSX.Element => {
  const { format } = useLocaleDateTimeFormat();
  const {
    tooltipLeft: addCommentTooltipLeft,
    tooltipTop: addCommentTooltipTop,
    tooltipOpen: addCommentTooltipOpen,
    showTooltip: showAddCommentTooltip,
    hideTooltip: hideAddCommentTooltip
  } = VisxTooltip.useTooltip();

  const memoProps = pick(propertiesToMemoize, props);

  return useMemoComponent({
    Component: (
      <GraphContent
        {...props}
        addCommentTooltipLeft={addCommentTooltipLeft}
        addCommentTooltipOpen={addCommentTooltipOpen}
        addCommentTooltipTop={addCommentTooltipTop}
        format={format}
        hideAddCommentTooltip={hideAddCommentTooltip}
        showAddCommentTooltip={showAddCommentTooltip}
      />
    ),
    memoProps: [...values(memoProps)]
  });
};

export default Graph;
